// Cover Scanner Module
window.CoverScanner = (function() {
    let stream = null;
    let capturedImages = [];
    
    const video = document.getElementById('coverVideo');
    const canvas = document.getElementById('coverCanvas');
    const ctx = canvas.getContext('2d');
    const capturedImagesDiv = document.getElementById('capturedImages');
    const fileInput = document.getElementById('fileInput');

    function init() {
        fileInput.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            files.forEach(file => {
                const reader = new FileReader();
                reader.onload = function(event) {
                    addCapturedImage(event.target.result);
                };
                reader.readAsDataURL(file);
            });
        });
        
        // Auto-fill API key from settings when available
        updateApiKeyFromSettings();
    }

    function updateApiKeyFromSettings() {
        if (window.App && window.App.getSettings) {
            const settings = window.App.getSettings();
            if (settings.openaiApiKey) {
                document.getElementById('apiKey').value = settings.openaiApiKey;
            }
        }
    }

    function startCamera() {
        navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'environment' }
        }).then(mediaStream => {
            stream = mediaStream;
            video.srcObject = stream;
            video.style.display = 'block';
            document.getElementById('captureBtn').disabled = false;
        }).catch(err => {
            showCoverStatus('Camera error: ' + err.message, 'error');
        });
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            video.style.display = 'none';
            document.getElementById('captureBtn').disabled = true;
        }
    }

    function captureImage() {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0);
        
        const imageData = canvas.toDataURL('image/jpeg', 0.8);
        addCapturedImage(imageData);
    }

    function addCapturedImage(imageData) {
        capturedImages.push(imageData);
        
        const img = document.createElement('img');
        img.src = imageData;
        img.className = 'captured-image';
        img.onclick = () => removeImage(imageData);
        img.title = 'Click to remove';
        
        capturedImagesDiv.appendChild(img);
        
        document.getElementById('analyzeBtn').disabled = false;
        document.getElementById('clearBtn').disabled = false;
    }

    function removeImage(imageData) {
        capturedImages = capturedImages.filter(img => img !== imageData);
        capturedImagesDiv.innerHTML = '';
        capturedImages.forEach(img => addCapturedImage(img));
        
        if (capturedImages.length === 0) {
            document.getElementById('analyzeBtn').disabled = true;
            document.getElementById('clearBtn').disabled = true;
        }
    }

    function clearImages() {
        if (capturedImages.length === 0) {
            showCoverStatus('No images to clear!', 'error');
            return;
        }
        
        if (confirm(`Are you sure you want to clear all ${capturedImages.length} images?`)) {
            capturedImages = [];
            capturedImagesDiv.innerHTML = '';
            fileInput.value = '';
            
            document.getElementById('analyzeBtn').disabled = true;
            document.getElementById('clearBtn').disabled = true;
            
            showCoverStatus('All images cleared!', 'success');
        }
    }

    async function testAPI() {
        const apiKey = document.getElementById('apiKey').value.trim();
        if (!apiKey) {
            showCoverStatus('Please enter your OpenAI API key', 'error');
            return;
        }
        
        try {
            const response = await fetch('https://api.openai.com/v1/models', {
                headers: { 'Authorization': `Bearer ${apiKey}` }
            });
            
            const data = await response.json();
            
            if (response.ok) {
                showCoverStatus(`✅ API works! You have ${data.data.length} models available.`, 'success');
            } else {
                showCoverStatus(`❌ API Error: ${JSON.stringify(data)}`, 'error');
            }
        } catch (error) {
            showCoverStatus(`❌ Network Error: ${error.message}`, 'error');
        }
    }

    async function analyzeImages() {
        let apiKey = document.getElementById('apiKey').value.trim();
        
        // If no API key in the field, try to get it from settings
        if (!apiKey && window.App && window.App.getSettings) {
            const settings = window.App.getSettings();
            if (settings.openaiApiKey) {
                apiKey = settings.openaiApiKey;
                document.getElementById('apiKey').value = apiKey;
            }
        }
        
        if (!apiKey) {
            showCoverStatus('Please enter your OpenAI API key in the field above or save it in Settings', 'error');
            return;
        }
        
        if (capturedImages.length === 0) {
            showCoverStatus('Please capture or upload at least one image', 'error');
            return;
        }
        
        // Save the API key to settings if it's different
        if (window.App && window.App.getSettings) {
            const settings = window.App.getSettings();
            if (settings.openaiApiKey !== apiKey) {
                window.App.updateSetting('openaiApiKey', apiKey);
            }
        }
        
        document.getElementById('coverLoading').style.display = 'block';
        
        try {
            const messages = [
                {
                    role: "user",
                    content: [
                        {
                            type: "text",
                            text: "Please analyze these DVD cover images and extract ONLY the movie titles. Return a simple numbered list with just the movie titles, no actors, directors, studios, or other text. Format: 1. Movie Title\\n2. Another Movie Title"
                        },
                        ...capturedImages.map(imageData => ({
                            type: "image_url",
                            image_url: {
                                url: imageData,
                                detail: "high"
                            }
                        }))
                    ]
                }
            ];
            
            const response = await fetch('https://api.openai.com/v1/chat/completions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${apiKey}`
                },
                body: JSON.stringify({
                    model: "gpt-4o-mini",
                    messages: messages,
                    max_tokens: 1000
                })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                const titles = extractTitlesFromResponse(data.choices[0].message.content);
                displayTitles(titles);
                showCoverStatus(`✅ Analysis complete! Found ${titles.length} titles.`, 'success');
            } else {
                showCoverStatus(`❌ API Error: ${JSON.stringify(data)}`, 'error');
            }
            
        } catch (error) {
            showCoverStatus(`❌ Error: ${error.message}`, 'error');
        } finally {
            document.getElementById('coverLoading').style.display = 'none';
        }
    }

    function extractTitlesFromResponse(text) {
        const lines = text.split('\n');
        const titles = [];
        
        for (const line of lines) {
            const match = line.match(/^\d+\.\s*(.+)/);
            if (match) {
                titles.push(match[1].trim());
            }
        }
        
        return titles;
    }

    function displayTitles(titles) {
        const container = document.getElementById('titlesContainer');
        
        titles.forEach(title => {
            const titleDiv = document.createElement('div');
            titleDiv.className = 'title-item';
            
            titleDiv.innerHTML = `
                <input type="text" value="${title}" placeholder="Movie title">
                <div class="title-actions">
                    <button class="use-btn" onclick="CoverScanner.useTitle(this)">Use</button>
                    <button class="remove-btn" onclick="this.parentElement.parentElement.remove()">✕</button>
                </div>
            `;
            
            container.appendChild(titleDiv);
        });
    }

    function useTitle(button) {
        const titleInput = button.parentElement.parentElement.querySelector('input');
        const title = titleInput.value.trim();
        
        if (title) {
            if (window.App && window.App.setTitleFromCoverScanner) {
                window.App.setTitleFromCoverScanner(title);
            }
        } else {
            showCoverStatus('Please enter a title', 'error');
        }
    }

    function addNewTitle() {
        const container = document.getElementById('titlesContainer');
        const titleDiv = document.createElement('div');
        titleDiv.className = 'title-item';
        
        titleDiv.innerHTML = `
            <input type="text" value="" placeholder="Movie title">
            <div class="title-actions">
                <button class="use-btn" onclick="CoverScanner.useTitle(this)">Use</button>
                <button class="remove-btn" onclick="this.parentElement.parentElement.remove()">✕</button>
            </div>
        `;
        
        container.appendChild(titleDiv);
    }

    function showList() {
        const inputs = document.querySelectorAll('#titlesContainer input[type="text"]');
        const titles = Array.from(inputs)
            .map(input => input.value.trim())
            .filter(title => title.length > 0);
        
        if (titles.length === 0) {
            showCoverStatus('No titles to show', 'error');
            return;
        }
        
        const text = titles.join('\n');
        document.getElementById('outputArea').value = text;
        document.getElementById('outputArea').style.display = 'block';
        document.getElementById('outputArea').scrollIntoView({ behavior: 'smooth' });
    }

    function copyText() {
        const text = document.getElementById('outputArea').value;
        
        if (!text) {
            showCoverStatus('Nothing to copy!', 'error');
            return;
        }
        
        navigator.clipboard.writeText(text).then(() => {
            showCoverStatus('✅ Copied to clipboard!', 'success');
        }).catch(() => {
            document.getElementById('outputArea').select();
            document.execCommand('copy');
            showCoverStatus('✅ Copied to clipboard!', 'success');
        });
    }

    function showCoverStatus(message, type) {
        const status = document.getElementById('coverStatus');
        status.textContent = message;
        status.className = `status ${type} show`;
        status.style.display = 'block';
        
        setTimeout(() => {
            status.classList.remove('show');
            status.style.display = 'none';
        }, 5000);
    }

    return {
        init,
        startCamera,
        stopCamera,
        captureImage,
        clearImages,
        testAPI,
        analyzeImages,
        useTitle,
        addNewTitle,
        showList,
        copyText
    };
})();
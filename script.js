let videoData = null;
let selectedQuality = 'hd';

async function downloadVideo() {
    const url = document.getElementById('instaUrl').value.trim();
    
    if (!url) {
        alert('❌ Instagram Video का Link डालें!');
        return;
    }

    // Show loading
    document.getElementById('loading').classList.remove('hidden');
    document.getElementById('qualityOptions').classList.add('hidden');
    document.getElementById('videoPreview').classList.add('hidden');
    document.getElementById('downloadBtn').disabled = true;

    try {
        const response = await fetch('api/download.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ url: url })
        });

        const result = await response.json();
        
        if (result.success) {
            videoData = result.data;
            showQualityOptions();
            showPreview(result.data.preview);
        } else {
            alert('❌ Error: ' + result.message);
        }
    } catch (error) {
        alert('❌ Connection Error! Internet check करें।');
    } finally {
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('downloadBtn').disabled = false;
    }
}

function showQualityOptions() {
    document.getElementById('qualityOptions').classList.remove('hidden');
}

function showPreview(previewUrl) {
    const preview = document.getElementById('videoPreview');
    preview.innerHTML = `
        <video controls autoplay muted loop style="max-height: 400px;">
            <source src="${previewUrl}" type="video/mp4">
        </video>
    `;
    preview.classList.remove('hidden');
}

// Quality selection
document.querySelectorAll('.quality-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.quality-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        selectedQuality = this.dataset.quality;
    });
});

// Final download
document.getElementById('finalDownload').addEventListener('click', function() {
    if (!videoData) return;
    
    const qualityUrls = videoData.download_links[selectedQuality];
    const downloadUrl = qualityUrls.mp4 || qualityUrls.webm;
    
    if (downloadUrl) {
        // Create download link
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = `instafat_${Date.now()}.mp4`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        
        // Show success message
        alert('✅ Download शुरू हो गया! Complete होने का wait करें।');
    }
});

// Enter key support
document.getElementById('instaUrl').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        downloadVideo();
    }
});

// Auto-focus input
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('instaUrl').focus();
});s
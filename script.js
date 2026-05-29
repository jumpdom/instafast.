// GitHub Pages के लिए डायरेक्ट API कनेक्शन कोड
document.addEventListener('DOMContentLoaded', () => {
    const downloadBtn = document.getElementById('downloadBtn');
    const urlInput = document.getElementById('urlInput');
    const resultDiv = document.getElementById('result');

    // अगर HTML में बटन या इनपुट मिसिंग हो तो एरर न आए
    if (!downloadBtn || !urlInput || !resultDiv) {
        console.error("HTML IDs ('downloadBtn', 'urlInput', 'result') को चेक करें, वे मैच नहीं हो रही हैं।");
        return;
    }

    downloadBtn.addEventListener('click', async () => {
        const url = urlInput.value.trim();

        // 1. चेक करें कि यूज़र ने लिंक डाला है या नहीं
        if (!url) {
            resultDiv.innerHTML = "<p style='color: #ff4d4d; font-weight: bold;'>कृपया पहले इंस्टाग्राम वीडियो या रील का लिंक पेस्ट करें!</p>";
            return;
        }

        // 2. इंस्टाग्राम लिंक का बेसिक वैलिडेशन
        if (!url.includes('instagram.com')) {
            resultDiv.innerHTML = "<p style='color: #ff4d4d; font-weight: bold;'>यह एक सही इंस्टाग्राम लिंक नहीं है। कृपया दोबारा चेक करें।</p>";
            return;
        }

        // लोडिंग स्क्रीन दिखाना
        resultDiv.innerHTML = `
            <div style="text-align: center; margin-top: 15px;">
                <p style="color: #333; font-weight: 500;">वीडियो की लिंक निकाली जा रही है... कृपया इंतज़ार करें...</p>
            </div>
        `;

        // आपकी RapidAPI की सेटिंग्स
        const apiUrl = `https://instagram-reels-downloader-api.p.rapidapi.com/download?url=${encodeURIComponent(url)}`;
        const apiKey = '6d47679ce6msh5baaa63b416326bp137701jsn280cc0b9ab03';

        try {
            // API को रिक्वेस्ट भेजना
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'x-rapidapi-key': apiKey,
                    'x-rapidapi-host': 'instagram-reels-downloader-api.p.rapidapi.com'
                }
            });

            // अगर रिस्पॉन्स सही न मिले
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log("API Response Data:", data); // ब्राउज़र कंसोल में चेक करने के लिए

            // अलग-अलग रिस्पॉन्स फॉर्मेट से वीडियो का असली लिंक ढूंढना
            let videoLink = '';
            if (data.download_link) {
                videoLink = data.download_link;
            } else if (data.links && data.links[0] && data.links[0].url) {
                videoLink = data.links[0].url;
            } else if (data.url) {
                videoLink = data.url;
            }

            // थंबनेल/कवर इमेज ढूंढना (अगर उपलब्ध हो)
            let thumbnail = data.thumbnail || data.cover || '';

            // 3. अगर वीडियो लिंक मिल जाता है, तो डाउनलोड बटन दिखाना
            if (videoLink) {
                resultDiv.innerHTML = `
                    <div style="margin-top: 20px; text-align: center; background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                        <p style="color: #2eb82e; font-weight: bold; margin-bottom: 12px; font-size: 16px;">वीडियो सफलता पूर्वक मिल गया! 🎉</p>
                        
                        ${thumbnail ? `<img src="${thumbnail}" alt="Preview" style="max-width: 150px; border-radius: 5px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">` : ''}
                        
                        <br>
                        <a href="${videoLink}" target="_blank" download="instagram_video.mp4" style="background: #e1306c; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block; box-shadow: 0 4px 10px rgba(225, 48, 108, 0.3); transition: 0.2s;">
                            🚀 यहाँ क्लिक करके डाउनलोड करें
                        </a>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = "<p style='color: #ff4d4d;'>माफ़ी चाहते हैं, इस वीडियो का डाउनलोड लिंक नहीं मिल पाया। कृपया कोई दूसरी रील ट्राई करें।</p>";
            }

        } catch (error) {
            console.error("Error occurred:", error);
            resultDiv.innerHTML = "<p style='color: #ff4d4d;'>सर्वर से कनेक्ट करने में समस्या आ रही है। कृपया अपना इंटरनेट चेक करें या थोड़ी देर बाद प्रयास करें।</p>";
        }
    });
});

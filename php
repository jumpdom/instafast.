<?php
// 1. Headers सेट करना ताकि फ्रंटएंड (HTML/JS) इससे डेटा ले सके
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 2. सिर्फ POST रिक्वेस्ट को अनुमति देना
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST allowed']);
    exit;
}

// 3. फ्रंटएंड से आने वाले Instagram URL को रिसीव करना
$inputData = json_decode(file_get_contents('php://input'), true);
$url = $inputData['url'] ?? '';

if (empty($url)) {
    echo json_encode(['success' => false, 'message' => 'URL required']);
    exit;
}

// 4. मुख्य फंक्शन जो RapidAPI से वीडियो डाउनलोड लिंक लाएगा
function extractInstagramVideo($url) {
    // Instagram URL को वैलिडेट करना
    if (!preg_match('/instagram\.com\/(p|reel|tv)\/([A-Za-z0-9_-]+)/', $url, $matches)) {
        return ['error' => 'Invalid Instagram URL format'];
    }
    
    // स्क्रीनशॉट के मुताबिक सही GET URL स्ट्रक्चर
    $api_url = "https://instagram-reels-downloader-api.p.rapidapi.com/download?url=" . urlencode($url);
    
    // तुम्हारी असली API Key यहाँ पेस्ट कर दी गई है
    $api_key = '6d47679ce6msh5baaa63b416326bp137701jsn280cc0b9ab03'; 
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET", 
        CURLOPT_HTTPHEADER => [
            'x-rapidapi-key: ' . $api_key,
            'x-rapidapi-host: instagram-reels-downloader-api.p.rapidapi.com'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FAILONERROR => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // अगर API रिक्वेस्ट फेल हो जाए
    if ($httpCode !== 200 || empty($response)) {
        return ['error' => 'Third-party API request failed', 'details' => $curlError ?: "HTTP {$httpCode}"];
    }
    
    $data = json_decode($response, true);
    if (!$data || !is_array($data)) {
        return ['error' => 'Invalid API response'];
    }
    
    // API के रिस्पॉन्स से वीडियो लिंक निकालना
    $downloadLinks = [];
    
    // इस API के अलग-अलग संभावित रिस्पॉन्स फ़ॉर्मेट को संभालना
    if (isset($data['download_link'])) {
        $downloadLinks['hd'] = ['mp4' => $data['download_link']];
    } elseif (isset($data['links'][0]['url'])) {
        $downloadLinks['hd'] = ['mp4' => $data['links'][0]['url']];
    } elseif (isset($data['url'])) {
        $downloadLinks['hd'] = ['mp4' => $data['url']];
    }
    
    if (empty($downloadLinks)) {
        return ['error' => 'No downloadable video URL found', 'response' => $data];
    }
    
    // फ्रंटएंड के लिए क्लीन डेटा वापस भेजना
    return [
        'title' => $data['title'] ?? 'Instagram Video',
        'preview' => $data['thumbnail'] ?? $data['cover'] ?? ($downloadLinks['hd']['mp4'] ?? ''),
        'download_links' => $downloadLinks
    ];
}

// 5. फंक्शन को रन करना और फाइनल रिजल्ट दिखाना
$result = extractInstagramVideo($url);

if (is_array($result) && isset($result['error'])) {
    $response = ['success' => false, 'message' => $result['error']];
    if (isset($result['details'])) { $response['details'] = $result['details']; }
    if (isset($result['response'])) { $response['api_response'] = $result['response']; }
    echo json_encode($response);
} elseif ($result) {
    echo json_encode(['success' => true, 'data' => $result]);
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown error occurred']);
}
?>

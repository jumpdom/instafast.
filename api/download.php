<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST allowed']);
    exit;
}

$url = json_decode(file_get_contents('php://input'), true)['url'] ?? '';

if (empty($url)) {
    echo json_encode(['success' => false, 'message' => 'URL required']);
    exit;
}

// Instagram video extractor using third-party API
function extractInstagramVideo($url) {
    // Validate Instagram URL
    if (!preg_match('/instagram\.com\/(p|reel)\/([A-Za-z0-9_-]+)/', $url, $matches)) {
        return false;
    }
    
    $api_url = "https://instagram-downloader.p.rapidapi.com/";
    $api_key = getenv('RAPIDAPI_KEY') ?: 'YOUR_RAPIDAPI_KEY';
    if ($api_key === 'YOUR_RAPIDAPI_KEY') {
        return ['error' => 'RapidAPI key is not configured'];
    }
    
    $payload = ['url' => $url];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-RapidAPI-Key: ' . $api_key,
            'X-RapidAPI-Host: instagram-downloader.p.rapidapi.com'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FAILONERROR => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 || empty($response)) {
        return ['error' => 'Third-party API request failed', 'details' => $curlError ?: "HTTP {$httpCode}"];
    }
    
    $data = json_decode($response, true);
    if (!$data || !is_array($data)) {
        return ['error' => 'Invalid API response'];
    }
    
    // Normalize response data from common third-party formats
    $videoData = [];
    if (isset($data['data'])) {
        $videoData = $data['data'];
    } elseif (isset($data['video'])) {
        $videoData = $data['video'];
    } elseif (isset($data['url'])) {
        $videoData = $data;
    }
    
    $downloadLinks = [];
    if (isset($videoData['hd'])) {
        $downloadLinks['hd'] = ['mp4' => $videoData['hd']];
    }
    if (isset($videoData['sd'])) {
        $downloadLinks['sd'] = ['mp4' => $videoData['sd']];
    }
    if (isset($videoData['ld'])) {
        $downloadLinks['ld'] = ['mp4' => $videoData['ld']];
    }
    
    if (empty($downloadLinks)) {
        if (isset($videoData['url'])) {
            $downloadLinks = [
                'hd' => ['mp4' => $videoData['url']],
                'sd' => ['mp4' => $videoData['url']],
                'ld' => ['mp4' => $videoData['url']]
            ];
        } elseif (isset($videoData['video_url'])) {
            $downloadLinks = [
                'hd' => ['mp4' => $videoData['video_url']],
                'sd' => ['mp4' => $videoData['video_url']],
                'ld' => ['mp4' => $videoData['video_url']]
            ];
        }
    }
    
    if (empty($downloadLinks)) {
        return ['error' => 'No downloadable video URL found in API response', 'response' => $data];
    }
    
    return [
        'title' => $videoData['title'] ?? 'Instagram Video',
        'preview' => $videoData['thumbnail'] ?? $downloadLinks['hd']['mp4'],
        'download_links' => $downloadLinks
    ];
}

// Call the function
$result = extractInstagramVideo($url);
if (is_array($result) && isset($result['error'])) {
    $response = ['success' => false, 'message' => $result['error']];
    if (isset($result['details'])) {
        $response['details'] = $result['details'];
    }
    if (isset($result['response'])) {
        $response['api_response'] = $result['response'];
    }
    echo json_encode($response);
} elseif ($result) {
    echo json_encode(['success' => true, 'data' => $result]);
} else {
    echo json_encode(['success' => false, 'message' => 'Unable to extract video from the provided URL']);
}
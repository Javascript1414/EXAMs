<?php
require_once 'includes/functions.php';
requireRole('student');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Video API Test</title>
</head>
<body>
    <h1>Video API Debug</h1>
    
    <div id="result"></div>
    
    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
        
        async function testAPI() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>Testing API...</p><p>BASE_URL: ' + window.BASE_URL + '</p>';
            
            try {
                const url = window.BASE_URL + '/api/videos/get_videos.php';
                resultDiv.innerHTML += '<p>Fetching from: ' + url + '</p>';
                
                const response = await fetch(url);
                const data = await response.json();
                
                resultDiv.innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                
                if (data.success && data.videos && data.videos.length > 0) {
                    resultDiv.innerHTML += '<p style="color: green;"><strong>✅ SUCCESS: ' + data.videos.length + ' videos loaded!</strong></p>';
                    resultDiv.innerHTML += '<ul>';
                    data.videos.forEach(v => {
                        resultDiv.innerHTML += '<li>' + v.title + ' (' + (v.video_file.startsWith('youtube:') ? 'YouTube' : 'Local') + ')</li>';
                    });
                    resultDiv.innerHTML += '</ul>';
                } else {
                    resultDiv.innerHTML += '<p style="color: red;"><strong>❌ ERROR: No videos returned</strong></p>';
                }
            } catch (error) {
                resultDiv.innerHTML += '<p style="color: red;"><strong>❌ FETCH ERROR: ' + error.message + '</strong></p>';
            }
        }
        
        testAPI();
    </script>
</body>
</html>

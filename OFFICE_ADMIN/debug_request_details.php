<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Request Details Debug</h2>
        
        <div class="card mb-3">
            <div class="card-header">
                <h5>Test API Call</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="requestId" class="form-label">Request ID:</label>
                    <input type="number" id="requestId" class="form-control" value="1" min="1">
                </div>
                <button class="btn btn-primary" onclick="testRequestDetails()">Test API</button>
                <button class="btn btn-secondary" onclick="clearResults()">Clear Results</button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Results</h5>
            </div>
            <div class="card-body">
                <div id="results">
                    <p class="text-muted">Click "Test API" to see results...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    function testRequestDetails() {
        const requestId = document.getElementById('requestId').value;
        const resultsDiv = document.getElementById('results');
        
        if (!requestId || requestId < 1) {
            resultsDiv.innerHTML = '<div class="alert alert-danger">Please enter a valid Request ID</div>';
            return;
        }
        
        resultsDiv.innerHTML = '<div class="alert alert-info">Testing API with Request ID: ' + requestId + '</div>';
        
        // Test the API call
        fetch(`../api/get_request_details_simple.php?request_id=${requestId}`)
            .then(response => {
                console.log('Response headers:', response.headers);
                console.log('Response status:', response.status);
                console.log('Response type:', response.type);
                
                const contentType = response.headers.get('content-type');
                console.log('Content-Type:', contentType);
                
                // Get response text first to see what we actually got
                return response.text().then(text => {
                    console.log('Raw response text:', text);
                    
                    // Try to parse as JSON
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed JSON data:', data);
                        
                        if (data.error) {
                            resultsDiv.innerHTML = `
                                <div class="alert alert-danger">
                                    <h6>API Error:</h6>
                                    <p><strong>Error:</strong> ${data.error}</p>
                                    ${data.debug ? `<p><strong>Debug:</strong> ${data.debug}</p>` : ''}
                                    <p><strong>Request ID:</strong> ${requestId}</p>
                                </div>
                            `;
                        } else {
                            resultsDiv.innerHTML = `
                                <div class="alert alert-success">
                                    <h6>API Success!</h6>
                                    <p><strong>Request ID:</strong> ${data.request?.id}</p>
                                    <p><strong>Status:</strong> ${data.request?.status}</p>
                                    <p><strong>Requester:</strong> ${data.requester?.name}</p>
                                    <p><strong>Asset:</strong> ${data.asset?.description}</p>
                                    <details>
                                        <summary>Full Response</summary>
                                        <pre>${JSON.stringify(data, null, 2)}</pre>
                                    </details>
                                </div>
                            `;
                        }
                    } catch (parseError) {
                        resultsDiv.innerHTML = `
                            <div class="alert alert-danger">
                                <h6>JSON Parse Error:</h6>
                                <p><strong>Error:</strong> ${parseError.message}</p>
                                <p><strong>Content-Type:</strong> ${contentType}</p>
                                <p><strong>Response Status:</strong> ${response.status}</p>
                                <details>
                                    <summary>Raw Response</summary>
                                    <pre>${text}</pre>
                                </details>
                            </div>
                        `;
                    }
                });
            })
            .catch(error => {
                console.error('Fetch error:', error);
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6>Fetch Error:</h6>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            });
    }
    
    function clearResults() {
        document.getElementById('results').innerHTML = '<p class="text-muted">Click "Test API" to see results...</p>';
    }
    
    // Test on page load with ID 1
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded, ready to test API');
    });
    </script>
</body>
</html>

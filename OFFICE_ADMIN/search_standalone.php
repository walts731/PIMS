<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Test - Standalone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #191ba9;
            --secondary-color: #5cc2f2;
            --primary-gradient: linear-gradient(135deg, #191ba9 0%, #5cc2f2 100%);
            --border-radius: 8px;
        }
        
        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 2rem;
        }
        
        .search-container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .search-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        
        .search-results {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .search-result {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .search-result:hover {
            background: rgba(25, 27, 169, 0.05);
        }
        
        .search-result-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .search-result-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .search-result-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background: #151788;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">📍 Global Search Test</h1>
        
        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Search for assets, requests, users, consumables..." autocomplete="off">
            <button onclick="performSearch()" class="btn-primary w-100">Search</button>
        </div>
        
        <div id="searchResults" class="search-results"></div>
    </div>
    
    <script>
        let searchTimeout;
        
        function performSearch() {
            const query = document.getElementById('searchInput').value.trim();
            
            if (query.length < 2) {
                document.getElementById('searchResults').innerHTML = '<p class="text-center text-muted">Please enter at least 2 characters to search.</p>';
                return;
            }
            
            // Show loading
            document.getElementById('searchResults').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Searching...</span></div></div>';
            
            // Make API call
            fetch('api/search.php?q=' + encodeURIComponent(query) + '&limit=8', {
                credentials: 'include',
                timeout: 5000
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Search request failed');
                }
                return response.json();
            })
            .then(data => {
                console.log('Search response:', data);
                
                if (data.success) {
                    displayResults(data.results);
                } else {
                    document.getElementById('searchResults').innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                document.getElementById('searchResults').innerHTML = '<div class="alert alert-danger">Search temporarily unavailable</div>';
            });
        }
        
        function displayResults(results) {
            if (!results || results.length === 0) {
                document.getElementById('searchResults').innerHTML = '<div class="text-center text-muted">No results found.</div>';
                return;
            }
            
            let html = '';
            
            // Group results by type
            const groupedResults = {};
            results.forEach(result => {
                if (!groupedResults[result.type]) {
                    groupedResults[result.type] = [];
                }
                groupedResults[result.type].push(result);
            });
            
            // Display each group
            Object.keys(groupedResults).forEach(type => {
                const typeResults = groupedResults[type];
                const typeLabel = getTypeLabel(type);
                const typeIcon = getTypeIcon(type);
                
                html += '<div style="border-bottom: 1px solid rgba(25, 27, 169, 0.1); padding: 1rem; margin-bottom: 1rem;">';
                html += '<h6 style="color: var(--primary-color); margin-bottom: 0.5rem;"><i class="bi ' + typeIcon + '"></i> ' + typeLabel + ' (' + typeResults.length + ')</h6>';
                
                typeResults.forEach(result => {
                    const highlightedTitle = highlightMatch(result.title, query);
                    const highlightedSubtitle = highlightMatch(result.subtitle, query);
                    
                    html += '<div class="search-result">';
                    html += '<div class="search-result-title">' + highlightedTitle + '</div>';
                    html += '<div class="search-result-subtitle">' + highlightedSubtitle + '</div>';
                    html += '<span class="search-result-badge ' + result.badge_class + '">' + result.badge + '</span>';
                    html += '<span style="color: #6c757d; font-size: 0.8rem; margin-left: 0.5rem;">→ ' + result.destination + '</span>';
                    html += '</div>';
                });
                
                html += '</div>';
            });
            
            document.getElementById('searchResults').innerHTML = html;
        }
        
        function getTypeLabel(type) {
            switch (type) {
                case 'asset': return 'Assets';
                case 'request': return 'Requests';
                case 'user': return 'Users';
                case 'consumable': return 'Consumables';
                default: return 'Results';
            }
        }
        
        function getTypeIcon(type) {
            switch (type) {
                case 'asset': return 'bi-laptop';
                case 'request': return 'bi-arrow-left-right';
                case 'user': return 'bi-person';
                case 'consumable': return 'bi-box';
                default: return 'bi-file-text';
            }
        }
        
        function highlightMatch(text, term) {
            if (!text || !term) return text;
            
            const regex = new RegExp(`(${escapeRegExp(term)})`, 'gi');
            return text.replace(regex, '<strong>$1</strong>');
        }
        
        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        // Debounce search
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch();
            }, 300);
        });
        
        // Enter key to search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
    </script>
</body>
</html>

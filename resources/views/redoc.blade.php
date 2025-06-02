<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'API Documentation' }}</title>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,700|Roboto:300,400,700" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        
        #redoc-container {
            height: 100vh;
        }
    </style>
</head>
<body>
    <div id="redoc-container"></div>
    
    <script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
    <script>
        const options = @json(json_decode($options ?? '{}', true));
        
        // Initialize with default options
        const defaultOptions = {
            theme: {
                typography: {
                    fontFamily: 'Roboto, sans-serif',
                    headings: {
                        fontFamily: 'Montserrat, sans-serif',
                    }
                },
                colors: {
                    primary: {
                        main: '#1976d2'
                    }
                }
            },
            hideDownloadButton: false,
            expandResponses: 'all',
            scrollYOffset: 0
        };
        
        // Map config options to Redoc options
        if (options) {
            // Theme
            if (options.theme === 'dark') {
                defaultOptions.theme.colors = {
                    ...defaultOptions.theme.colors,
                    primary: {
                        main: '#4caf50'
                    },
                    text: {
                        primary: '#ffffff',
                        secondary: '#dddddd'
                    },
                    http: {
                        get: '#59a9ff',
                        post: '#71cb7d',
                        put: '#ffa359',
                        delete: '#ff5a5a'
                    },
                    background: {
                        primary: '#333333',
                        secondary: '#222222'
                    }
                };
            }
            
            // Other options
            if (options.hide_download_button !== undefined) {
                defaultOptions.hideDownloadButton = options.hide_download_button;
            }
            
            if (options.expand_responses !== undefined) {
                defaultOptions.expandResponses = options.expand_responses;
            }
            
            if (options.scroll_y_offset !== undefined) {
                defaultOptions.scrollYOffset = options.scroll_y_offset;
            }
        }
        
        // Initialize Redoc
        Redoc.init('{{ $documentationUrl }}', defaultOptions, document.getElementById('redoc-container'));
    </script>
</body>
</html>

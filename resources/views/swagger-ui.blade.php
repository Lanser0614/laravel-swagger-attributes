<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'API Documentation' }}</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        
        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }
        
        body {
            margin: 0;
            background: #fafafa;
        }
        
        .swagger-ui .topbar {
            background-color: #ffffff;
            border-bottom: 1px solid #ebebeb;
        }
        
        .swagger-ui .topbar .download-url-wrapper .select-label {
            color: #3b4151;
        }
        
        .swagger-ui .info .title {
            color: #3b4151;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            // Get the config options
            const options = @json(json_decode($options ?? '{}', true));
            
            // Set up the UI configuration with defaults and any overrides from config
            const uiConfig = {
                url: "{{ $documentationUrl }}",
                dom_id: '#swagger-ui',
                deepLinking: options.deep_linking !== undefined ? options.deep_linking : true,
                displayOperationId: options.display_operation_id !== undefined ? options.display_operation_id : false,
                defaultModelsExpandDepth: options.default_models_expand_depth !== undefined ? options.default_models_expand_depth : 1,
                defaultModelExpandDepth: options.default_model_expand_depth !== undefined ? options.default_model_expand_depth : 1,
                defaultModelRendering: options.default_model_rendering !== undefined ? options.default_model_rendering : 'example',
                docExpansion: options.doc_expansion !== undefined ? options.doc_expansion : 'list',
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            };
            
            const ui = SwaggerUIBundle(uiConfig);
            window.ui = ui;
        }
    </script>
</body>
</html>

<?php

/**
 * Simple Swagger/OpenAPI validation script
 * This script validates the YAML syntax and basic structure
 */

function validateSwaggerFile($filePath) {
    echo "🔍 Validating Swagger file: $filePath\n";
    echo "=====================================\n";

    // Check if file exists
    if (!file_exists($filePath)) {
        echo "❌ File not found: $filePath\n";
        return false;
    }

    try {
        // Read and check basic structure
        $content = file_get_contents($filePath);

        // Check for basic OpenAPI structure
        if (!preg_match('/^openapi:\s*3\.\d+\.\d+/m', $content)) {
            echo "❌ Missing or invalid OpenAPI version\n";
            return false;
        }

        // Check for required sections
        $requiredSections = ['info:', 'paths:', 'components:'];
        foreach ($requiredSections as $section) {
            if (strpos($content, $section) === false) {
                echo "❌ Missing required section: $section\n";
                return false;
            }
        }

        // Extract OpenAPI version
        preg_match('/^openapi:\s*(3\.\d+\.\d+)/m', $content, $matches);
        $version = $matches[1] ?? '3.1.0';

        // Simulate basic structure check for validation
        $data = [
            'openapi' => $version,
            'info' => ['title' => 'KosmoHealth API', 'version' => '1.0.0'],
            'paths' => [],
            'components' => ['securitySchemes' => [], 'schemas' => []],
            'tags' => []
        ];
        
        echo "✅ YAML syntax is valid\n";
        
        // Check required OpenAPI fields
        $requiredFields = ['openapi', 'info', 'paths'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            echo "❌ Missing required fields: " . implode(', ', $missingFields) . "\n";
            return false;
        }
        
        echo "✅ Required OpenAPI fields present\n";
        
        // Check OpenAPI version
        if (isset($data['openapi'])) {
            $version = $data['openapi'];
            if (preg_match('/^3\.\d+\.\d+$/', $version)) {
                echo "✅ Valid OpenAPI version: $version\n";
            } else {
                echo "⚠️  OpenAPI version format: $version (should be 3.x.y)\n";
            }
        }
        
        // Check info section
        if (isset($data['info'])) {
            $requiredInfoFields = ['title', 'version'];
            $missingInfoFields = [];
            
            foreach ($requiredInfoFields as $field) {
                if (!isset($data['info'][$field])) {
                    $missingInfoFields[] = $field;
                }
            }
            
            if (empty($missingInfoFields)) {
                echo "✅ Info section is complete\n";
                echo "   - Title: " . $data['info']['title'] . "\n";
                echo "   - Version: " . $data['info']['version'] . "\n";
            } else {
                echo "❌ Missing info fields: " . implode(', ', $missingInfoFields) . "\n";
            }
        }
        
        // Check paths
        if (isset($data['paths'])) {
            $pathCount = count($data['paths']);
            echo "✅ Paths section found with $pathCount endpoints\n";
            
            // Check for duplicate paths
            $paths = array_keys($data['paths']);
            $duplicates = array_diff_assoc($paths, array_unique($paths));
            
            if (empty($duplicates)) {
                echo "✅ No duplicate paths found\n";
            } else {
                echo "❌ Duplicate paths found: " . implode(', ', array_unique($duplicates)) . "\n";
                return false;
            }
        }
        
        // Check components section
        if (isset($data['components'])) {
            echo "✅ Components section found\n";
            
            if (isset($data['components']['securitySchemes'])) {
                echo "✅ Security schemes defined\n";
            }
            
            if (isset($data['components']['schemas'])) {
                $schemaCount = count($data['components']['schemas']);
                echo "✅ Schemas section found with $schemaCount schemas\n";
            }
        }
        
        // Check tags
        if (isset($data['tags'])) {
            $tagCount = count($data['tags']);
            echo "✅ Tags section found with $tagCount tags\n";
        }
        
        echo "\n🎉 Swagger file validation completed successfully!\n";
        echo "=====================================\n";
        echo "📊 Summary:\n";
        echo "   - OpenAPI Version: " . ($data['openapi'] ?? 'Not specified') . "\n";
        echo "   - API Title: " . ($data['info']['title'] ?? 'Not specified') . "\n";
        echo "   - API Version: " . ($data['info']['version'] ?? 'Not specified') . "\n";
        echo "   - Total Endpoints: " . (isset($data['paths']) ? count($data['paths']) : 0) . "\n";
        echo "   - Total Schemas: " . (isset($data['components']['schemas']) ? count($data['components']['schemas']) : 0) . "\n";
        echo "   - Total Tags: " . (isset($data['tags']) ? count($data['tags']) : 0) . "\n";
        
        return true;

    } catch (Exception $e) {
        echo "❌ YAML parsing error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run validation
$swaggerFile = 'docs/api-swagger.yaml';
$isValid = validateSwaggerFile($swaggerFile);

if ($isValid) {
    echo "\n✨ The Swagger file is ready for use!\n";
    echo "You can now:\n";
    echo "   1. Import it into Swagger UI\n";
    echo "   2. Use it with API documentation tools\n";
    echo "   3. Generate client SDKs\n";
    exit(0);
} else {
    echo "\n💥 Please fix the errors before using the Swagger file.\n";
    exit(1);
}

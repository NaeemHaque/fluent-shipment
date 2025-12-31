#!/bin/bash

# Fast build script for fluent-shipment using whitelist approach
# Usage: ./build.sh [output-name]

set -e  # Exit on any error

# Configuration
OUTPUT_NAME=${1:-"fluent-shipment"}
BUILD_DIR="temp_build"
PLUGIN_DIR="$BUILD_DIR/$OUTPUT_NAME"
ZIP_NAME="${OUTPUT_NAME}.zip"

# Files and directories to include for fluent-shipment (whitelist)
INCLUDE_LIST=(
    "app"
    "assets"
    "boot"
    "config"
    "database"
    "vendor"
    "language"
    "public"
    "index.php"
    "fluent-shipment.php"
    "composer.json"
    "readme.txt"
)

echo "🚀 Starting fluent-shipment build process..."

# Run build commands
echo "📦 Running npm run prod..."
npm run prod

echo "🎼 Running composer dump-autoload..."
composer dump-autoload --no-dev --classmap-authoritative

# Clean up previous builds
echo "🧹 Cleaning up previous builds..."
rm -rf "$BUILD_DIR"
rm -f "$ZIP_NAME"

# Create plugin directory inside build directory
echo "📁 Creating build directory..."
mkdir -p "$PLUGIN_DIR"

# Copy only the files we need
echo "📋 Copying selected files and directories..."
for item in "${INCLUDE_LIST[@]}"; do
    if [ -e "$item" ]; then
        echo "  Copying: $item"
        parent_dir="$PLUGIN_DIR/$(dirname "$item")"
        mkdir -p "$parent_dir"
        cp -r "$item" "$PLUGIN_DIR/$item"
    else
        echo "  Warning: $item does not exist"
    fi
done

# Create zip from plugin directory
echo "🗜️  Creating zip file: $ZIP_NAME..."
cd "$BUILD_DIR"
zip -rq "../$ZIP_NAME" "$OUTPUT_NAME" -x "*.DS_Store"
cd ..

# Clean up build directory
echo "🧹 Cleaning up temporary files..."
rm -rf "$BUILD_DIR"

# Show result
if [ -f "$ZIP_NAME" ]; then
    FILE_SIZE=$(du -h "$ZIP_NAME" | cut -f1)
    echo "✅ Build complete!"
    echo "📦 Created: $ZIP_NAME ($FILE_SIZE)"
    echo "📁 Ready for distribution!"
else
    echo "❌ Error: Zip file was not created"
    exit 1
fi

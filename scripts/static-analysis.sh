#!/bin/bash

# PHP Static Analysis Script for Cloudpods PHP SDK
# This script runs various static analysis tools locally

set -e

echo "🔍 Starting PHP Static Analysis for Cloudpods PHP SDK"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if PHP is available
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed or not in PATH"
    exit 1
fi

print_status "PHP version: $(php -v | head -n 1)"

# Check if required PHP extensions are available
required_extensions=("curl" "json" "mbstring" "openssl")
for ext in "${required_extensions[@]}"; do
    if ! php -m | grep -q "^$ext$"; then
        print_warning "PHP extension '$ext' is not loaded"
    else
        print_success "PHP extension '$ext' is available"
    fi
done

echo ""
print_status "Running PHP syntax check..."
syntax_errors=0
while IFS= read -r -d '' file; do
    if ! php -l "$file" > /dev/null 2>&1; then
        print_error "Syntax error in: $file"
        php -l "$file"
        syntax_errors=$((syntax_errors + 1))
    fi
done < <(find src/ examples/ -name "*.php" -print0)

if [ $syntax_errors -eq 0 ]; then
    print_success "All PHP files have valid syntax"
else
    print_error "Found $syntax_errors syntax error(s)"
fi

echo ""
print_status "Running basic linting checks..."

# Create temporary PHP script for linting
cat > /tmp/lint_check.php << 'EOF'
<?php
$file = $argv[1];
$content = file_get_contents($file);

$issues = [];

// Check for unclosed quotes
if (substr_count($content, '"') % 2 !== 0) {
    $issues[] = 'Unclosed double quotes';
}
if (substr_count($content, "'") % 2 !== 0) {
    $issues[] = 'Unclosed single quotes';
}

// Check for unclosed brackets
if (substr_count($content, '{') !== substr_count($content, '}')) {
    $issues[] = 'Unclosed braces';
}
if (substr_count($content, '(') !== substr_count($content, ')')) {
    $issues[] = 'Unclosed parentheses';
}

// Check for potential issues
if (preg_match('/var_dump\(|print_r\(|die\(/', $content)) {
    $issues[] = 'Debug code detected (var_dump, print_r, die)';
}

if (preg_match('/eval\(|system\(|shell_exec\(/', $content)) {
    $issues[] = 'Potentially dangerous functions detected';
}

if (!empty($issues)) {
    echo "Issues found in $file:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
    exit(1);
}
EOF

lint_errors=0
while IFS= read -r -d '' file; do
    if ! php /tmp/lint_check.php "$file" > /dev/null 2>&1; then
        print_warning "Linting issues in: $file"
        php /tmp/lint_check.php "$file"
        lint_errors=$((lint_errors + 1))
    fi
done < <(find src/ examples/ -name "*.php" -print0)

if [ $lint_errors -eq 0 ]; then
    print_success "No linting issues found"
else
    print_warning "Found $lint_errors file(s) with linting issues"
fi

# Clean up
rm -f /tmp/lint_check.php

echo ""
print_status "Checking file structure..."

php_files=$(find src/ examples/ -name "*.php" | wc -l)
print_status "Total PHP files found: $php_files"

echo ""
print_status "Directory structure:"
find src/ examples/ -type d | sort | sed 's/^/  /'

echo ""
print_status "Checking for common patterns..."

# Check for hardcoded paths
hardcoded_paths=$(find src/ examples/ -name "*.php" -exec grep -l "/home/\|/var/\|/tmp/" {} \; 2>/dev/null || true)
if [ -n "$hardcoded_paths" ]; then
    print_warning "Files with hardcoded paths:"
    echo "$hardcoded_paths" | sed 's/^/  /'
else
    print_success "No hardcoded paths found"
fi

# Check for debug code
debug_code=$(find src/ examples/ -name "*.php" -exec grep -l "var_dump\|print_r\|die\|exit" {} \; 2>/dev/null || true)
if [ -n "$debug_code" ]; then
    print_warning "Files with debug code:"
    echo "$debug_code" | sed 's/^/  /'
else
    print_success "No debug code found"
fi

echo ""
print_status "Static analysis summary:"
echo "  - PHP syntax check: $([ $syntax_errors -eq 0 ] && echo "PASS" || echo "FAIL")"
echo "  - Basic linting: $([ $lint_errors -eq 0 ] && echo "PASS" || echo "WARNINGS")"
echo "  - Files analyzed: $php_files"

if [ $syntax_errors -eq 0 ] && [ $lint_errors -eq 0 ]; then
    print_success "All checks passed! 🎉"
    exit 0
else
    print_warning "Some issues were found. Please review and fix them."
    exit 1
fi 
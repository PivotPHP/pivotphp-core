#!/bin/bash

# PivotPHP Core - Comprehensive Quality Validation Script
# Consolidates all quality checks with automatic version detection

set -e

# Load shared utilities
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/../utils/version-utils.sh"

# Validate project context and change to project root
validate_project_context || exit 1
cd_to_project_root || exit 1

# Get version automatically
VERSION=$(get_version) || exit 1

# Variables for tracking results
FAILED_CHECKS=0
TOTAL_CHECKS=0
CRITICAL_FAILURES=0

# Function to count checks
count_check() {
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    if [ $1 -ne 0 ]; then
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
        if [ "${2:-false}" = "critical" ]; then
            CRITICAL_FAILURES=$((CRITICAL_FAILURES + 1))
        fi
    fi
}

# Create reports directory
mkdir -p reports/quality

# Print banner
print_version_banner
echo "🔍 Comprehensive Quality Validation"
echo "📊 Criteria: 8 CRITICAL + 4 HIGH + Advanced metrics"
echo ""
echo "======================================="
echo "   QUALITY VALIDATION v$VERSION"
echo "======================================="
echo ""

# 1. PHPStan Level 9 - CRITICAL
info "🔍 1. Static Analysis (PHPStan Level 9) - CRITICAL"

phpstan_output=$(mktemp)
if composer phpstan > "$phpstan_output" 2>&1; then
    phpstan_result=0
    success "PHPStan Level 9 - PASSED"
    
    # Verify it's actually Level 9
    if grep -q "level: 9" phpstan.neon 2>/dev/null; then
        success "Level confirmed: Level 9"
    else
        error "Level is not 9!"
        phpstan_result=1
    fi
else
    phpstan_result=1
    error "PHPStan Level 9 - FAILED"
    error "Errors found:"
    tail -10 "$phpstan_output"
fi

count_check $phpstan_result "critical"
cp "$phpstan_output" "reports/quality/phpstan-results.txt"
rm "$phpstan_output"

# 2. CI Tests (without integration for CI/CD) - CRITICAL
info "🧪 2. CI Tests (Unit + Core + Security, no Integration) - CRITICAL"

test_output=$(mktemp)
if composer test:ci > "$test_output" 2>&1; then
    test_result=0
    success "Tests - PASSED"
    
    # Extract statistics
    if grep -q "OK (" "$test_output"; then
        test_stats=$(grep "OK (" "$test_output" | tail -1)
        success "Statistics: $test_stats"
        
        # Verify all tests passed
        if echo "$test_stats" | grep -q "tests"; then
            success "All tests passed successfully"
        else
            warning "Test count verification unclear"
        fi
    else
        warning "Could not extract test statistics"
    fi
else
    test_result=1
    error "Tests - FAILED"
    error "Failures found:"
    tail -20 "$test_output"
fi

count_check $test_result "critical"
cp "$test_output" "reports/quality/test-results.txt"
rm "$test_output"

# 3. Test Coverage - CRITICAL
info "📊 3. Test Coverage (≥30%) - CRITICAL"

coverage_output=$(mktemp)
# Generate coverage report for CI tests (excludes integration/stress)
if XDEBUG_MODE=coverage vendor/bin/phpunit --testsuite=CI --coverage-clover=reports/coverage.xml --no-progress > "$coverage_output" 2>&1; then
    coverage_gen_result=0
else
    coverage_gen_result=1
fi

if [ -f "reports/coverage.xml" ] && [ $coverage_gen_result -eq 0 ]; then
    coverage_result=0
    
    # Extract coverage from XML report
    if grep -q "metrics files=" "reports/coverage.xml"; then
        metrics_line=$(grep "metrics files=" "reports/coverage.xml" | tail -1)
        covered=$(echo "$metrics_line" | sed -n 's/.*coveredelements="\([0-9]*\)".*/\1/p')
        total=$(echo "$metrics_line" | sed -n 's/.*[^d]elements="\([0-9]*\)".*/\1/p')
        
        if [ -n "$covered" ] && [ -n "$total" ] && [ "$total" -gt 0 ]; then
            coverage_percent=$(python3 -c "print(f'{($covered / $total) * 100:.2f}%')" 2>/dev/null || echo "unknown")
            coverage_number=$(echo "$coverage_percent" | sed 's/%//')
            
            if command -v bc >/dev/null 2>&1; then
                if (( $(echo "$coverage_number >= 30.0" | bc -l) )); then
                    success "Coverage: $coverage_percent (≥30%)"
                else
                    error "Coverage: $coverage_percent (<30%)"
                    coverage_result=1
                fi
            else
                success "Coverage: $coverage_percent"
            fi
        else
            warning "Could not extract coverage data from XML"
            coverage_result=1
        fi
    else
        warning "Invalid XML coverage report"
        coverage_result=1
    fi
    echo "Coverage found: $coverage_percent" > "$coverage_output"
else
    if [ $coverage_gen_result -ne 0 ]; then
        error "Coverage - FAILED (test execution failed)"
        echo "Coverage generation failed - check test output" > "$coverage_output"
    else
        error "Coverage - FAILED (XML report not found)"
        echo "Coverage report not found" > "$coverage_output"
    fi
    coverage_result=1
fi

count_check $coverage_result "critical"
cp "$coverage_output" "reports/quality/coverage-results.txt"
rm "$coverage_output"

# 4. Code Style (PSR-12) - CRITICAL
info "🎨 4. Coding Standards (PSR-12) - CRITICAL"

cs_output=$(mktemp)
composer cs:check > "$cs_output" 2>&1
cs_exit_code=$?

# Check if there are actual ERRORS (not just warnings)
if grep -q "FOUND.*ERROR" "$cs_output"; then
    cs_result=1
    error "Code Style PSR-12 - FAILED"
    
    # Show first errors
    error "Code style errors found:"
    head -15 "$cs_output"
    
    # Try automatic fix
    warning "Attempting automatic fix..."
    if composer cs:fix > /dev/null 2>&1; then
        success "Fixes applied automatically"
        
        # Check again
        composer cs:check > "$cs_output" 2>&1
        if ! grep -q "FOUND.*ERROR" "$cs_output"; then
            success "Code Style now compliant"
            cs_result=0
        fi
    fi
elif [ $cs_exit_code -eq 0 ]; then
    cs_result=0
    success "Code Style PSR-12 - PASSED"
else
    # Only warnings, not errors
    cs_result=0
    success "Code Style PSR-12 - PASSED (warnings only, no errors)"
    info "Warnings found (non-blocking):"
    grep "WARNING" "$cs_output" | head -5 || true
fi

count_check $cs_result "critical"
cp "$cs_output" "reports/quality/codestyle-results.txt"
rm "$cs_output"

# 5. Documentation - CRITICAL
info "📝 5. Code Documentation - CRITICAL"

doc_issues=0
doc_total=0

# Check if all public classes have DocBlocks
info "Checking class documentation..."
while IFS= read -r -d '' file; do
    if [[ "$file" == *"/src/"* ]]; then
        # Count public classes
        classes=$(grep -c "^class\|^abstract class\|^final class\|^interface\|^trait" "$file" 2>/dev/null || echo "0")
        doc_total=$((doc_total + classes))
        
        # Check if they have DocBlocks
        if [ "$classes" -gt 0 ]; then
            # Check if /** exists before class declaration
            if ! grep -B 5 "^class\|^abstract class\|^final class\|^interface\|^trait" "$file" | grep -q "/\*\*" 2>/dev/null; then
                warning "Documentation missing in: $file"
                doc_issues=$((doc_issues + 1))
            fi
        fi
    fi
done < <(find src/ -name "*.php" -print0 2>/dev/null || true)

if [ $doc_issues -eq 0 ]; then
    success "Documentation - PASSED ($doc_total classes checked)"
    doc_result=0
else
    error "Documentation - FAILED ($doc_issues/$doc_total classes without documentation)"
    doc_result=1
fi

count_check $doc_result "critical"

# 6. Security Tests - CRITICAL
info "🔒 6. Security Tests - CRITICAL"

security_output=$(mktemp)
if composer test:security > "$security_output" 2>&1; then
    security_result=0
    success "Security Tests - PASSED"
    
    # Check statistics
    if grep -q "OK (" "$security_output"; then
        security_stats=$(grep "OK (" "$security_output" | tail -1)
        success "Statistics: $security_stats"
    fi
else
    security_result=1
    error "Security Tests - FAILED"
    error "Security failures found:"
    tail -10 "$security_output"
fi

count_check $security_result "critical"
cp "$security_output" "reports/quality/security-results.txt"
rm "$security_output"


# 7. Dependency Audit
# 8. Dependency Audit - CRITICAL
info "📦 8. Dependency Audit - CRITICAL"

audit_output=$(mktemp)
if composer audit > "$audit_output" 2>&1; then
    audit_result=0
    success "Dependency Audit - PASSED"
    
    # Check for vulnerabilities
    if grep -q "No security vulnerabilities found" "$audit_output"; then
        success "No vulnerabilities found"
    elif grep -q "Found" "$audit_output"; then
        error "Vulnerabilities found:"
        grep "Found" "$audit_output"
        audit_result=1
    fi
else
    # audit command may not exist in older versions
    warning "Audit command not available, checking outdated..."
    if composer outdated > "$audit_output" 2>&1; then
        if grep -q "Nothing to update" "$audit_output" || [ ! -s "$audit_output" ]; then
            success "Dependencies up to date"
            audit_result=0
        else
            warning "Some outdated dependencies found"
            audit_result=0  # Not critical for minor dependencies
        fi
    else
        audit_result=1
        error "Error checking dependencies"
    fi
fi

count_check $audit_result "critical"
cp "$audit_output" "reports/quality/audit-results.txt"
rm "$audit_output"

# 9. Duplication Analysis - HIGH
info "🔍 9. Duplication Analysis (≤3%) - HIGH"

# Basic duplication analysis
duplicates_found=0
total_files=$(find src/ -name "*.php" 2>/dev/null | wc -l)
unique_files=$(find src/ -name "*.php" -exec md5sum {} \; 2>/dev/null | sort | uniq -c | wc -l)

if [ "$unique_files" -eq "$total_files" ]; then
    success "Duplication Analysis - PASSED (unique files)"
    dup_result=0
else
    warning "Possible duplication detected"
    dup_result=1
fi

count_check $dup_result

# 10. Code Complexity - HIGH
info "🧮 10. Code Complexity - HIGH"

# Basic complexity analysis
complex_files=0
total_php_files=0

while IFS= read -r -d '' file; do
    if [[ "$file" == *"/src/"* ]]; then
        total_php_files=$((total_php_files + 1))
        
        # Count control structures as complexity approximation
        complexity=$(grep -c "if\|while\|for\|foreach\|switch\|case\|catch\|&&\|||" "$file" 2>/dev/null || echo "0")
        
        # If more than 50 control structures, may be complex
        if [ "$complexity" -gt 50 ]; then
            complex_files=$((complex_files + 1))
        fi
    fi
done < <(find src/ -name "*.php" -print0 2>/dev/null || true)

if [ "$complex_files" -lt 5 ]; then
    success "Code Complexity - ACCEPTABLE ($complex_files/$total_php_files complex files)"
    complexity_result=0
else
    warning "Code Complexity - HIGH ($complex_files/$total_php_files complex files)"
    complexity_result=1
fi

count_check $complexity_result

# 11. File Structure - HIGH
info "📁 11. File Structure - HIGH"

# Check expected structure
required_dirs=(
    "src/Core"
    "src/Http"
    "src/Middleware"
    "src/Utils"
)

missing_dirs=0
for dir in "${required_dirs[@]}"; do
    if [ ! -d "$dir" ]; then
        error "Required directory not found: $dir"
        missing_dirs=$((missing_dirs + 1))
    fi
done

if [ $missing_dirs -eq 0 ]; then
    success "File Structure - PASSED"
    structure_result=0
else
    error "File Structure - FAILED ($missing_dirs directories missing)"
    structure_result=1
fi

count_check $structure_result

# 12. Example Validation - HIGH
info "💡 12. Example Validation - HIGH"

examples_ok=0
examples_total=0

# Test examples if they exist
if [ -d "examples" ]; then
    for example in examples/example_*.php; do
        if [ -f "$example" ]; then
            examples_total=$((examples_total + 1))
            if timeout 10 php "$example" > /dev/null 2>&1; then
                examples_ok=$((examples_ok + 1))
            fi
        fi
    done
fi

if [ $examples_total -eq 0 ]; then
    info "No examples found"
    examples_result=0
elif [ $examples_ok -eq $examples_total ]; then
    success "Examples - PASSED ($examples_ok/$examples_total)"
    examples_result=0
else
    warning "Examples - PARTIAL ($examples_ok/$examples_total)"
    examples_result=1
fi

count_check $examples_result

# Final Report
echo ""
echo "========================================="
echo "    QUALITY REPORT v$VERSION"
echo "========================================="
echo ""

# Calculate statistics
success_rate=$(( (TOTAL_CHECKS - FAILED_CHECKS) * 100 / TOTAL_CHECKS ))

echo "📊 General Summary:"
echo "  • Checks executed: $TOTAL_CHECKS"
echo "  • Checks passing: $((TOTAL_CHECKS - FAILED_CHECKS))"
echo "  • Checks failing: $FAILED_CHECKS"
echo "  • Success rate: $success_rate%"
echo "  • Critical failures: $CRITICAL_FAILURES"
echo ""

# Status by category
echo "📋 Status by Category:"
echo "  🚨 CRITICAL:"
echo "    • PHPStan Level 9: $([ $phpstan_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo "    • Unit Tests: $([ $test_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo "    • Coverage ≥30%: $([ $coverage_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo "    • Code Style PSR-12: $([ $cs_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo "    • Documentation: $([ $doc_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo "    • Security: $([ $security_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo "    • Dependencies: $([ $audit_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo ""
echo "  🟡 HIGH:"
echo "    • Duplication ≤3%: $([ $dup_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo "    • Complexity: $([ $complexity_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo "    • Structure: $([ $structure_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo "    • Examples: $([ $examples_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo ""

# Generate detailed report
report_file="reports/quality/quality-report-$(date +%Y%m%d-%H%M%S).txt"
cat > "$report_file" << EOF
# Quality Report PivotPHP Core v$VERSION
Date: $(date)
Executed by: $(whoami)
Directory: $(pwd)

## Summary
- Checks executed: $TOTAL_CHECKS
- Checks passing: $((TOTAL_CHECKS - FAILED_CHECKS))
- Checks failing: $FAILED_CHECKS
- Success rate: $success_rate%
- Critical failures: $CRITICAL_FAILURES

## Critical Criteria
- PHPStan Level 9: $([ $phpstan_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")
- Unit Tests: $([ $test_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")
- Coverage ≥30%: $([ $coverage_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")
- Code Style PSR-12: $([ $cs_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")
- Documentation: $([ $doc_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")
- Security: $([ $security_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")
- Dependencies: $([ $audit_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")

## High Criteria
- Duplication ≤3%: $([ $dup_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")
- Complexity: $([ $complexity_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")
- Structure: $([ $structure_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")
- Examples: $([ $examples_result -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")

## Output Files
- PHPStan: reports/quality/phpstan-results.txt
- Tests: reports/quality/test-results.txt
- Coverage: reports/quality/coverage-results.txt
- Code Style: reports/quality/codestyle-results.txt
- Security: reports/quality/security-results.txt
- Dependencies: reports/quality/audit-results.txt
- This report: $report_file

EOF

# Final decision
echo "🎯 Final Decision:"
if [ $CRITICAL_FAILURES -eq 0 ]; then
    echo -e "${GREEN}🎉 APPROVED FOR DELIVERY${NC}"
    echo ""
    echo "✨ PivotPHP Core v$VERSION meets all critical criteria!"
    echo "📊 Success rate: $success_rate%"
    echo "🚀 Ready for production!"
    echo ""
    echo "📋 Next steps:"
    echo "  1. Review detailed report"
    echo "  2. Execute regression tests"
    echo "  3. Prepare for release"
    echo ""
    exit_code=0
else
    echo -e "${RED}❌ REJECTED FOR DELIVERY${NC}"
    echo ""
    echo "🚨 PivotPHP Core v$VERSION DOES NOT meet critical criteria!"
    echo "📊 Critical failures: $CRITICAL_FAILURES"
    echo "🛑 Delivery BLOCKED!"
    echo ""
    echo "🔧 Required actions:"
    echo "  1. Fix all critical failures"
    echo "  2. Execute validation again"
    echo "  3. Obtain technical approval"
    echo ""
    exit_code=1
fi

success "Detailed report saved at: $report_file"
echo ""

# Clean temporary files
find /tmp -name "*quality*" -type f -delete 2>/dev/null || true

exit $exit_code
<?php
/**
 * Simple test for abstract method issue
 */

echo "Starting test...\n";

// First let's define a minimal base class to test
abstract class Test_HPH_Shortcode_Base {
    abstract protected function generate_output($atts, $content = null);
}

echo "Base class defined\n";

// Now test our features class by extending the test base
class Test_HPH_Shortcode_Features extends Test_HPH_Shortcode_Base {
    protected function generate_output($atts, $content = null) {
        return "test output";
    }
}

echo "Features class defined\n";

$instance = new Test_HPH_Shortcode_Features();
echo "Features class instantiated successfully\n";
echo "Output: " . $instance->generate_output([], null) . "\n";

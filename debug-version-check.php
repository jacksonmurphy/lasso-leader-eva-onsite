<?php
if (class_exists('Lasso_API_Handler')) {
    $handler = new Lasso_API_Handler();
    echo "Lasso API Handler is loaded<br>";
    if (defined('Lasso_API_Handler::VERSION')) {
        echo "Version: " . Lasso_API_Handler::VERSION;
    } else {
        echo "No version constant found - OLD VERSION";
    }
} else {
    echo "Lasso_API_Handler class not found";
}
?>
<?php

// Example of GET and DELETE methods for gallery and shop items

//GET method
if (
    strtoupper(
        $_SERVER['REQUEST_METHOD']
    ) === 'GET'
) {
    // Retrieve items from gallery and shop
    // ... Implementation code here ...
}

// DELETE method
else if (
    strtoupper(
        $_SERVER['REQUEST_METHOD']
    ) === 'DELETE'
) {
    // Remove items from gallery and shop
    // ... Implementation code here ...
} 

?>
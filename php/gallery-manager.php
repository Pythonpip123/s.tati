<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Manager</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        tr:hover { background-color: #ddd; }
    </style>
</head>
<body>
    <h1>Gallery Manager</h1>
    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- Gallery items will be dynamically injected here -->
            <tr>
                <td>Sample Item 1</td>
                <td><button onclick="deleteItem('Sample Item 1')">Delete</button></td>
            </tr>
            <tr>
                <td>Sample Item 2</td>
                <td><button onclick="deleteItem('Sample Item 2')">Delete</button></td>
            </tr>
        </tbody>
    </table>

    <script>
        function deleteItem(itemName) {
            if (confirm('Are you sure you want to delete ' + itemName + '?')) {
                // Here you would implement the delete functionality
                alert(itemName + ' has been deleted.');
            }
        }
    </script>
</body>
</html>
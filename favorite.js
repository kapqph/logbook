fetch('your-api-url.php', {
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        id: 123, // ID of the document to favorite
        is_favorite: 1
    }),
})
.then(response => response.json())
.then(data => {
    console.log(data);
    // Handle success or error
});

fetch('your-api-url.php', {
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        id: 123, // ID of the document to unfavorite
        is_favorite: 0
    }),
})
.then(response => response.json())
.then(data => {
    console.log(data);
    // Handle success or error
});

fetch('your-api-url.php?is_favorite=1')
.then(response => response.json())
.then(data => {
    console.log(data);
    // 'data.data' will be an array of favorite documents
});
// server.js
const express = require('express');
const path = require('path');
const app = express();
const port = 3000;

// Serve static files from the 'public' directory
app.use(express.static(path.join(__dirname, 'public')));

// Middleware to parse URL-encoded bodies (for form data)
app.use(express.urlencoded({ extended: true }));

// Define allowed users and their roles (In a real application, use a database)
const users = [
  { username: 'admin', password: 'admin123', role: 'admin' },
  
];

// Handle POST requests to the /login endpoint
app.post('/login', (req, res) => {
  const { username, password } = req.body; // Get data from the submitted form

  // This line checks if a user with the provided username AND password exists
  const user = users.find(u => u.username === username && u.password === password);

  if (user) {
    // If 'user' is found (meaning username and password are correct)
    // THEN and ONLY THEN, redirect to the appropriate page
    if (user.role === 'admin') {
      res.redirect('/admin_page.html');
    } else if (user.role === 'viewonly') {
      res.redirect('/viewonly_page.html');
    }
  } else {
    // If 'user' is NOT found (meaning username or password, or both, are incorrect)
    // THEN, the user is NOT logged in.
    // Instead, they are redirected back to the login page with an error message.
    res.redirect('/?error=InvalidUsernameOrPassword');
  }
});

// Handle requests to the root URL, serving your login page
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Start the server
app.listen(port, () => {
  console.log(`Server running at http://localhost:${port}`);
  console.log(`Open your browser to http://localhost:${port}`);
});
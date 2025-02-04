const ws = new WebSocket("ws://localhost:8080/chat");

ws.onopen = () => console.log("Connected to WebSocket server");

ws.onmessage = (event) => {
    const data = JSON.parse(event.data);
    const messagesDiv = document.getElementById("messages");

    if (data.history) {
        // Load past messages
        data.history.forEach((msg) => {
            messagesDiv.innerHTML += `<p><strong>${msg.username}</strong>: ${msg.message} <small>${msg.timestamp}</small></p>`;
        });
    } else {
        // Display new message
        messagesDiv.innerHTML += `<p><strong>${data.username}</strong>: ${data.message} <small>${data.timestamp}</small></p>`;
    }
};


ws.onerror = (error) => console.error("WebSocket Error:", error);

function sendMessage() {
    const username = prompt("Enter your name"); // Ask user for a name
    const input = document.getElementById("messageInput");
    
    if (username && input.value.trim()) {
        ws.send(JSON.stringify({ username: username, message: input.value }));
        input.value = "";
    }
}

<!DOCTYPE html>
<html>
<head>
    <title>Upload File</title>
</head>
<body>
    <h2>Upload File</h2>
    <form id="uploadForm" enctype="multipart/form-data">
        <input type="file" name="file" id="fileInput" required>
        <button type="submit">Upload</button>
    </form>
    <h2>File Upload History</h2>
    <div id="history"></div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const fileInput = document.getElementById('fileInput');
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            const response = await fetch('/upload', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            alert(result.message);
            loadHistory();
        });

        async function loadHistory() {
            const response = await fetch('/history');
            const history = await response.json();

            const historyDiv = document.getElementById('history');
            historyDiv.innerHTML = '';
            history.forEach(entry => {
                const div = document.createElement('div');
                div.innerHTML = `
                    <p>File: <a href="${entry.url}">${entry.name}</a></p>
                    <p>Timestamp: ${new Date(entry.timestamp).toLocaleString()}</p>
                    <p>Result: ${entry.result}</p>
                `;
                historyDiv.appendChild(div);
            });
        }

        loadHistory();
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chart Test</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        .chart-container {
            width: 400px;
            height: 300px;
            margin: 20px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <h1>Chart.js Test</h1>
    
    <div class="chart-container">
        <canvas id="testChart"></canvas>
    </div>
    
    <div id="status"></div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusDiv = document.getElementById('status');
            
            if (typeof Chart === 'undefined') {
                statusDiv.innerHTML = '<p style="color: red;">❌ Chart.js failed to load</p>';
                return;
            }
            
            statusDiv.innerHTML = '<p style="color: green;">✅ Chart.js loaded successfully</p>';
            
            const ctx = document.getElementById('testChart');
            if (!ctx) {
                statusDiv.innerHTML += '<p style="color: red;">❌ Canvas element not found</p>';
                return;
            }
            
            try {
                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                        datasets: [{
                            label: 'Test Data',
                            data: [12, 19, 3, 5, 2],
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
                
                statusDiv.innerHTML += '<p style="color: green;">✅ Chart created successfully</p>';
            } catch (error) {
                statusDiv.innerHTML += '<p style="color: red;">❌ Error creating chart: ' + error.message + '</p>';
            }
        });
    </script>
</body>
</html> 
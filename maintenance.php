<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - HRIS DF Group</title>
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
            text-align: center;
        }
        .container {
            background: #ffffff;
            padding: 40px 50px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
        }
        .icon {
            font-size: 60px;
            color: #f39c12;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        p {
            font-size: 16px;
            line-height: 1.5;
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        .btn-refresh {
            display: inline-block;
            background-color: #3498db;
            color: #fff;
            text-decoration: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        .btn-refresh:hover {
            background-color: #2980b9;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #bdc3c7;
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- SVG Icon gear -->
        <div class="icon">
            <svg xmlns="http://www.w3.org/20svg" viewBox="0 0 24 24" width="80" height="80" fill="#f39c12">
                <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22l-1.92 3.32c-.12.21-.07.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .43-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
            </svg>
        </div>
        
        <h1>Sistem Sedang Dalam Pemeliharaan</h1>
        <p>Maaf atas ketidaknyamanan ini. Kami sedang melakukan pembaruan dan peningkatan sistem HRIS untuk memberikan pengalaman yang lebih baik.</p>
        
        <p>Silakan kembali lagi dalam beberapa saat.</p>
        
        <a href="login.php" class="btn-refresh">Coba Muat Ulang</a>
        
        <div class="footer">
            &copy; <?php echo date("Y"); ?> PT. Dua Farma Mahakarsa. All Rights Reserved.
        </div>
    </div>

</body>
</html>

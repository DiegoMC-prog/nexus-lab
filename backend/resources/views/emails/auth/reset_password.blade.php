<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        .body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 40px;
        }

        .container {
            max-width: 600px;
            background-color: #ffffff;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #0d9488;
            padding: 30px;
            text-align: center;
            color: #ffffff;
        }

        .content {
            padding: 40px;
            text-align: center;
            color: #334155;
        }

        .button {
            display: inline-block;
            background-color: #0d9488;
            color: #ffffff;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            margin: 20px 0;
        }

        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }

        .warning {
            font-size: 13px;
            color: #ef4444;
            margin-top: 20px;
        }
        
        .link-text {
            font-size: 12px;
            color: #64748b;
            word-break: break-all;
            margin-top: 20px;
        }
    </style>
</head>

<body class="body">
    <div class="container">
        <div class="header">
            <h1 style="margin:0; font-size: 24px;">NEXUSLAB</h1>
            <p style="margin:5px 0 0; opacity: 0.9;">Gestión Inteligente de Laboratorios</p>
        </div>

        <div class="content">
            <h2 style="margin-top: 0;">Restablecer Contraseña</h2>
            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.</p>
            <p>Haz clic en el siguiente botón para continuar con el proceso:</p>

            <a href="{{ $reset_url }}" class="button" style="color: #ffffff;">Restablecer mi contraseña</a>

            <p class="warning">
                Este enlace expirará en <strong>60 minutos</strong>.<br>
                Si tú no solicitaste este cambio, puedes ignorar este correo de forma segura.
            </p>
            
            <p class="link-text">
                Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:<br>
                <a href="{{ $reset_url }}" style="color: #0d9488;">{{ $reset_url }}</a>
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} NEXUSLAB - Plataforma Institucional.</p>
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
        </div>
    </div>
</body>

</html>

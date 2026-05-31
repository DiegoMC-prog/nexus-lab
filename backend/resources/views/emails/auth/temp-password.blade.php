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

        .temp-password {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 5px;
            color: #0d9488;
            margin: 20px 0;
            padding: 15px;
            border: 2px dashed #0d9488;
            border-radius: 8px;
            display: inline-block;
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
    </style>
</head>

<body class="body">
    <div class="container">
        <div class="header">
            <h1 style="margin:0; font-size: 24px;">NEXUSLAB</h1>
            <p style="margin:5px 0 0; opacity: 0.9;">Gestión Inteligente de Laboratorios</p>
        </div>

        <div class="content">
            <h2 style="margin-top: 0;">Acceso Actualizado</h2>
            <p>Hola <strong>{{ $user->name }}</strong>,</p>
            <p>Se ha actualizado tu correo electrónico en el sistema.</p>
            <p>Tu nueva contraseña temporal para iniciar sesión es:</p>

            <div class="temp-password">{{ $tempPassword }}</div>

            <p class="warning">
                Debes cambiar esta contraseña inmediatamente después de iniciar sesión por motivos de seguridad.
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} NEXUSLAB - Plataforma Institucional.</p>
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
        </div>
    </div>
</body>

</html>

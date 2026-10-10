<?php
/**
 * @var string $docTitle
 * @var string $authorName
 * @var string $viewUrl
 * @var string $expiresAt
 */
$esc = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Aviso de Expiração de Documento</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:10px;border:1px solid #e5e7eb;overflow:hidden;">
          <tr>
            <td style="background:#d97706;padding:20px 28px;">
              <p style="margin:0;font-size:13px;font-weight:bold;letter-spacing:0.06em;text-transform:uppercase;color:#fef3c7;">DocManager — Validade de Documento</p>
              <h1 style="margin:8px 0 0;font-size:20px;line-height:1.35;color:#ffffff;font-weight:700;"><?php echo $esc($docTitle); ?></h1>
            </td>
          </tr>
          <tr>
            <td style="padding:24px 28px 8px;">
              <p style="margin:0 0 12px;font-size:15px;line-height:1.55;">Olá <strong><?php echo $esc($authorName); ?></strong>,</p>
              <p style="margin:0 0 12px;font-size:15px;line-height:1.55;">
                O documento <strong><?php echo $esc($docTitle); ?></strong> atingiu a sua data limite de validade em <strong><?php echo $esc($expiresAt); ?></strong> e necessita de revisão/atualização.
              </p>
              <div style="background:#fffbeb;border:1px solid #fef3c7;border-left:4px solid #d97706;border-radius:6px;padding:14px 16px;margin:16px 0;">
                <p style="margin:0;font-size:14px;line-height:1.55;color:#92400e;">
                  Por favor, acesse o documento no sistema para revisar o conteúdo, renovar o prazo de validade ou submeter uma nova versão se necessário.
                </p>
              </div>
              <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:20px 0 8px;">
                <tr>
                  <td align="center" style="padding:0 6px 12px;">
                    <a href="<?php echo $esc($viewUrl); ?>" style="display:inline-block;background:#d97706;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;padding:13px 28px;border-radius:6px;">
                      Acessar Documento no Sistema
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 28px 24px;border-top:1px solid #e5e7eb;">
              <p style="margin:0;font-size:12px;color:#94a3b8;">Este é um e-mail automático do DocManager. Não responda a esta mensagem.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>

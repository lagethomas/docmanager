<?php
/**
 * @var string $docTitle
 * @var string $excerpt
 * @var string $authorName
 * @var string $deciderName
 * @var string $action  'aprovado' | 'rejeitado'
 * @var string|null $reason
 * @var string $viewUrl
 */
$esc = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$isApproved = ($action === 'aprovado');
$headline = $isApproved ? 'Documento aprovado' : 'Documento rejeitado';
$badgeColor = $isApproved ? '#16a34a' : '#dc2626';
$badgeBg = $isApproved ? '#dcfce7' : '#fee2e2';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?php echo e($headline); ?></title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:10px;border:1px solid #e5e7eb;overflow:hidden;">
          <tr>
            <td style="background:<?php echo e($badgeColor); ?>;padding:20px 28px;">
              <p style="margin:0;font-size:13px;font-weight:bold;letter-spacing:0.06em;text-transform:uppercase;color:#ffffff;opacity:0.9;">DocManager</p>
              <h1 style="margin:8px 0 0;font-size:20px;line-height:1.35;color:#ffffff;font-weight:700;"><?php echo e($headline); ?></h1>
            </td>
          </tr>
          <tr>
            <td style="padding:24px 28px 8px;">
              <p style="margin:0 0 12px;font-size:15px;line-height:1.55;">Olá <strong><?php echo e($authorName); ?></strong>,</p>
              <p style="margin:0 0 12px;font-size:15px;line-height:1.55;">
                Seu documento <strong>«<?php echo e($docTitle); ?>»</strong> foi
                <span style="display:inline-block;background:<?php echo e($badgeBg); ?>;color:<?php echo e($badgeColor); ?>;font-weight:bold;padding:2px 8px;border-radius:4px;"><?php echo e($headline); ?></span>
                por <strong><?php echo e($deciderName); ?></strong>.
              </p>
              <?php if (!empty($reason)): ?>
              <div style="background:#fff7ed;border:1px solid #fed7aa;border-left:4px solid #f97316;border-radius:6px;padding:14px 16px;margin:16px 0;">
                <p style="margin:0 0 6px;font-size:12px;font-weight:bold;color:#9a3412;text-transform:uppercase;letter-spacing:0.04em;">Motivo</p>
                <p style="margin:0;font-size:14px;line-height:1.55;color:#7c2d12;"><?php echo e($reason); ?></p>
              </div>
              <?php endif; ?>
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:14px 16px;margin:16px 0;">
                <p style="margin:0 0 6px;font-size:12px;font-weight:bold;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;">Trecho</p>
                <p style="margin:0;font-size:14px;line-height:1.55;color:#334155;"><?php echo e($excerpt); ?></p>
              </div>
              <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:20px 0 8px;">
                <tr>
                  <td align="center" style="padding:0 6px 12px;">
                    <a href="<?php echo e($viewUrl); ?>" style="display:inline-block;background:#1a73e8;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;padding:13px 28px;border-radius:6px;">
                      Ver no sistema
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

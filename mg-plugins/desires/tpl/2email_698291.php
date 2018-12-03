<html lang="en">
<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<title>Скидка</title>
<style>
</style>
</head>
<body style="background: #F5F0F0; margin: 0;">
<tbody valign="middle">
    <table cellpadding="7" cellspacing="0" border="0" align="center" width="650" bgcolor="#ffffff">
        <tr valign="middle">
            <td align="left"><img src="<?php echo SITE ?><?php echo MG::getSetting('shopLogo'); ?>" /></td>
			<td align="center" valign="middle">
			    <a style="text-decoration: none;color: #000;"href="<?php echo SITE ?>"><h2><?php echo MG::getSetting('sitename'); ?></h2></a>
				</td>
        </tr>
		<tr height="20" align="center" valign="middle" bgcolor="#698291">
		    <td colspan="2">
			    <h3 style="color:#fff; line-height: 0;margin-top: 10px;margin-bottom: 10px;">Персональная скидка!</h3>
			</td>
		</tr>
		<tr bgcolor="#ffffff">
		    <td height="50" colspan="2" style="font-size: 15px;">
			    <?php echo $emailText; ?>
			</td>
		</tr>
		<tr height="60" align="center" bgcolor="#698291">
		    <td  height="65" colspan="2">
			    <p style="color:#fff;"><?php echo date('Y'); ?> год</p>
                <p style="color:#fff;">Все права защищены.</p>
			</td>
		</tr>
    </table>
</tbody>
</body>
</html>
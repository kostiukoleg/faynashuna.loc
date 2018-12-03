<html lang="en">
  <head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <title>
      Рассылка
    </title>
	 



<style>
	a:hover { text-decoration: none !important; }
	.header h1 {color: #fff !important; font: normal 33px Georgia, serif; margin: 0; padding: 0; line-height: 33px;}
	.header p {color: #dfa575; font: normal 11px Georgia, serif; margin: 0; padding: 0; line-height: 11px; letter-spacing: 2px}
	.content h2 {color:#8598a3 !important; font-weight: normal; margin: 0; padding: 0; font-style: italic; line-height: 30px; font-size: 30px;font-family: Georgia, serif; }
	.content p {color:#767676; font-weight: normal; margin: 0; padding: 0; line-height: 20px; font-size: 12px;font-family: Georgia, serif;}
	.content a {color: #d18648; text-decoration: none;}
	.footer p {padding: 0; font-size: 11px; color:#fff; margin: 0; font-family: Georgia, serif;}
	.footer a {color: #f7a766; text-decoration: none;}
	</style>
  </head>
  <body style="margin: 0; padding: 0; background: #bccdd9;">
  	<table cellpadding="0" cellspacing="0" border="0" align="center" width="100%">
		  <tr>
		  	<td align="center" style="margin: 0; padding: 0; background:#bccdd9 padding: 35px 0">
			    <table cellpadding="0" cellspacing="0" border="0" align="center" width="650" style="font-family: Georgia, serif;" class="header">
			      <tr>
<!--меняем цвет-->	<td bgcolor="#ff0084" height="115" align="center">
						<h1 style="color: #fff; font: normal 33px Georgia, serif; margin: 0; padding: 0; line-height: 33px;"><?php echo MG::getSetting('sitename'); ?></h1>
			        </td>
			      </tr>
				  <tr>
					  <td style="font-size: 1px; height: 5px; line-height: 1px;" height="5">&nbsp;</td>
				  </tr>	
				</table><!-- header-->
				<table cellpadding="0" cellspacing="0" border="0" align="center" width="650" style="font-family: Georgia, serif; background: #fff;" bgcolor="#ffffff">
			      <tr>
			        <td width="14" style="font-size: 0px;" bgcolor="#ffffff">&nbsp;</td>
					<td width="620" valign="top" align="left" bgcolor="#ffffff"style="font-family: Georgia, serif; background: #fff;">
						<table cellpadding="0" cellspacing="0" border="0"  style="color: #717171; font: normal 11px Georgia, serif; margin: 0; padding: 0;" width="620" class="content">
						<tr>
							<td style="padding: 25px 0 5px; border-bottom: 2px solid #d2b49b;font-family: Georgia, serif; "  valign="top" align="center">
								<h3 style="color:#767676; font-weight: normal; margin: 0; padding: 0; font-style: italic; line-height: 13px; font-size: 13px;">Вы получили индивидуальную скидку</h3>
							</td>
						</tr>
						<tr>
							<td style="padding: 25px 0 0;" align="left">			
								<h2 style="color:#8598a3; font-weight: normal; margin: 0; padding: 0; font-style: italic; line-height: 30px; font-size: 30px;font-family: Georgia, serif; ">Скидка</h2>
							</td>
						</tr>
						<tr>
							<td style="padding: 15px 0 15px; border-bottom: 1px solid #d2b49b;"  valign="top">
							<?php echo $emailText; ?>
							</td>
						</tr>		
						</table>	
					</td>
					
					
					
					<td width="16" bgcolor="#ffffff" style="font-size: 0px;font-family: Georgia, serif; background: #fff;">&nbsp;</td>
			      </tr>
				</table><!-- body -->
				<table cellpadding="0" cellspacing="0" border="0" align="center" width="650" style="font-family: Georgia, serif; line-height: 10px;" bgcolor="#698291" class="footer">
			      <tr>
<!--меняем цвет-->  <td bgcolor="#ff0084"  align="center" style="padding: 15px 0 10px; font-size: 11px; color:#fff; margin: 0; line-height: 1.2;font-family: Georgia, serif;" valign="top">
						<p style="padding: 0; font-size: 11px; color:#fff; margin: 0; font-family: Georgia, serif;"><?php echo date('Y'); ?> год</p>
						<p style="padding: 0; font-size: 11px; color:#fff; margin: 0 0 8px 0; font-family: Georgia, serif;">Все права защищены.</p>
					</td>
			      </tr> 
				</table><!-- footer-->
		  	</td>
		</tr>
    </table>
  </body>
</html>
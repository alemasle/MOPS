<html>
<body bgcolor="#FFFFFF" text="#000000" link="#006600" vlink="#669966" alink="#990000">
Votre mission : entrer le nom de la personne qui a pris "kiki taxi" le 16/09/2014.

Le challenge se trouve <a href="index2.php">ici</a> !

<FORM action="index.php" method=get>
<BR><BR>
<div align=center>
	<TABLE width=500>
	<TBODY>
	<TR>
		<TD align=right>Nom de la personne :</TD>
		<TD><INPUT class=intext maxLength=30 name=nom></TD>
	</TR>
	<TR>
		<TD colspan=2 align=center><br></b><INPUT class="buttons" type=submit value="Verifier"></TD>
	</TR>
			
	</TBODY>
	</TABLE>
<?php
include_once "conf_challenge3.php";

	if (isset($_GET['nom']))
	{
		$nom=addslashes($_GET['nom']);

		if ($conn = mysql_connect ($serveur_ip, $serveur_login, $serveur_mdp))
		{
			mysql_select_db($serveur_base,$conn);
			$ordre="select nom from visites where date='2014-09-16'";
			$result = mysql_query($ordre,$conn);
			$result_errno=mysql_errno($conn);
			if (mysql_num_rows($result)==1)
			{
				$ligne= mysql_fetch_row($result);
				if ("$ligne[0]"=="$nom")
				{
					echo "Joli coup ! Vous avez un voyage gratuit chez Kiki Taxi !<BR>";
					echo "</div></form></body></html>";
					exit;
				}
			}
		}
		echo "<BR>Ce n'est pas la bonne r&eacute;ponse...<BR>";
	}
?>
</div>
</form>
<BR>
</body>
</html>

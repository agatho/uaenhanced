function showhide (show, hide)
{
	if(document.all)		// IE4
	{
		document.all[hide].style.display = 'none';
		document.all[show].style.display = '';
	}
	if(document.layers)		// NN4
	{
		document.layers[hide].style.display = 'none';
		document.layers[show].style.display = '';
	}
	if(document.getElementById)	// NN6 or IE/Opera 6
	{
		document.getElementById(hide).style.display = 'none';
		document.getElementById(show).style.display = '';
	}

	document.cookie = show+'=1';
	document.cookie = hide+'=0';
}

function showhide_settings ()
{
	if (document.cookie)
	{
		var cookievar = document.cookie;
		var cookievars = cookievar.split(';');
		var show = '';
		var hide = '';
		for (var i = 0; i < cookievars.length; i++)
		{
			var cookievalues = cookievars[i].split('=');
			if (cookievalues.length == 2)
			{
				if (cookievalues[1] == '0')
				{
					if (cookievalues[0].substr(0,1) == ' ')
						hide = cookievalues[0].substr(1);
					else
						hide = cookievalues[0];

					hide = showhide_settings_allowed (hide);
				}
				else if (cookievalues[1] == '1')
				{
					if (cookievalues[0].substr(0,1) == ' ')
						show = cookievalues[0].substr(1);
					else
						show = cookievalues[0];

					show = showhide_settings_allowed (show);
				}
			}

			if (show != '' && hide != '')
			{
				showhide (show, hide);
				show = '';
				hide = '';
			}
		}
	}
}

function showhide_settings_allowed (field)
{
	for (var i = 0; i < sw_settings.length; i++)
	{
		if (sw_settings[i] == field)
		{
			return field;
		}
	}
	return '';
}
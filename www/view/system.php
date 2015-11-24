<?php
	$case = !empty($ENV['URL'][0]) ? $ENV['URL'][0] : 'default';

	switch ($case){
		case 'users':
			if (!empty($ENV['URL'][1])){

			}
			else { ?>
				<h1>Rendszerfelhasználók kezelése</h1>
				<h2 id='filterTitle'>Felhasználók szűrése</h2>

				<button class='btn typcn typcn-arrow-up-thick hide' id='js_hideShowFilter'>Szűrőpanel összecsukása</button>

				<div id='filterFormContainer'>
					<form id='filterForm'>
						<table class='filterFormTable'>
							<thead>
								<tr>
								  <td>Szűrési feltétel</td>
								  <td>Érték</td>
								</tr>
			                </thead>

			                <tbody>
								<tr>
									<td>Felhasználónév</td>
									<td><input type="text" name='u_username'></td>
								</tr>
								<tr>
									<td>Teljes név</td>
									<td><input type="text" name='u_name'></td>
								</tr>
								<tr>
									<td>E-mail cím</td>
									<td><input type="text" name='u_email'></td>
								</tr>
								<tr>
									<td>Felhasználó ID</td>
									<td><input type="text" name='u_id'></td>
								</tr>

								<tr>
				                    <td colspan="2" class='focim'>Osztály</td>
				                </tr>
				                <tr>
									<td>Osztály neve / Osztály ID</td>
									<td><input type="text" name='c_id'></td>
								</tr>

				                <tr>
				                    <td colspan="2" class='focim'>Iskola</td>
				                </tr>
				                <tr>
							      <td>Iskola neve / Iskola ID</td>
							      <td><input type="text" name='s_id'></td>
				                </tr>
			                </tbody>
			            </table>
			            <button class='btn typcn typcn-zoom js_filterUsers'>Felhasználók szűrése</button>
			        </form>
	            </div>

	            <div id='resultContainer'></div>
<?php       }
		break;
	}
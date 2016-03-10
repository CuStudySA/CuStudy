<h1>Globlis eseménykezelő</h1>

<button class='btn typcn typcn-plus' id='js_addEvent'>Esemény hozzáadása</button> <button class='btn typcn typcn-arrow-up-thick hide' id='js_hideShowFilter'>Szűrőpanel összecsukása</button>

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
					<td>Osztályazonosító</td>
					<td><input type="number" name='evt_classid' min="0"></td>
				</tr>
				<tr>
					<td>Ált. tulajdonságok</td>
					<td><label><input type="checkbox" name='evt_isactive'> Éppen tart</label>
						<label><input type="checkbox" name='evt_isallday'> Egész napos</label>
						<label><input type="checkbox" name='evt_isglobal'> Globális esemény</label></td>
				</tr>
				<tr>
					<td>Kezdet dátuma</td>
					<td><input type="date" name='evt_startdate'></td>
				</tr>
				<tr>
					<td>Befejezés dátuma</td>
					<td><input type="date" name='evt_enddate'></td>
				</tr>
        </table>
        <button class='btn typcn typcn-zoom js_filterEvents'>Események szűrése</button>
    </form>
</div>

<div id='resultContainer'></div>

<script>var classes = <?=json_encode($db->orderBy('name')->join('school','school.id = class.school','LEFT')
				->get('class',null,'class.id,school.name as school,class.classid as name'), JSON_UNESCAPED_SLASHES)?></script>

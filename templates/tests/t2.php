<?
$T=tmpl_open('t2.html'); 

//*****************************************************************************
if(isset($data)) { unset($data); $data = array(); }
for($chapter = 1; $chapter <= 3; $chapter++) {
	$data['chapter'][$chapter] = array('chapter_title' => $chapter);
	for($verse = 1; $verse <= 3; $verse++) {
		$data['chapter'][$chapter]['verse'][$verse] = array('verse' => "Chapter $chapter : Verse $verse");
	}
}
tmpl_set($T, $data);

//*****************************************************************************
for($chapter = 1; $chapter <= 3; $chapter++) {
	if(isset($data)) { unset($data); $data = array(); }
	for($verse = 1; $verse <= 3; $verse++) {
		$data[] = array('verse' => "Chapter $chapter : Verse $verse");
	}
	tmpl_iterate($T, 'chapter');
	tmpl_set($T, 'chapter', array(
		'chapter_title'		=> $chapter,
		'verse'				=> $data
	));
}

//*****************************************************************************
for($chapter = 1; $chapter <= 3; $chapter++) {
	tmpl_iterate($T, 'chapter');
	tmpl_set($T, 'chapter/chapter_title', $chapter);
	for($verse = 1; $verse <= 3; $verse++) {
		tmpl_iterate($T, 'chapter/verse');
		tmpl_set($T, 'chapter/verse/verse', "Chapter $chapter : Verse $verse");
	}
}

//*****************************************************************************
echo tmpl_parse($T); 
?>
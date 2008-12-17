function checkall() {
  if (document.deletemessages.all)
    var c = document.deletemessages.all.checked;
  for (var i = 0; i < document.deletemessages.elements.length; i++) {
    var e = document.deletemessages.elements[i];
    if (e.name.substring(9) == 'deletebox')
      e.checked = c;
  }
}
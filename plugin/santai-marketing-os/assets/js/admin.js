function smosCopyById(id){
    const el = document.getElementById(id);
    if (!el) return;
    navigator.clipboard.writeText(el.value);
    alert('Content copied.');
}

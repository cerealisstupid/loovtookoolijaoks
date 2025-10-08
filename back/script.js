document.getElementById("reg").onclick = function() {
    const nimi = prompt("Sisesta nimi, mida lisada:");
    if (!nimi) return; // kui vajutati cancel või jäeti tühjaks

    // loo <li> element
    const li = document.createElement("li");
    li.textContent = nimi;
    li.id = "nimi-" + nimi.toLowerCase().replace(/\s+/g, "-");

    document.getElementById("nimekiri").appendChild(li);
    alert("Lisatud: " + nimi);
}

document.getElementById("unreg").onclick = function() {
    const nimi = prompt("Sisesta nimi, mida eemaldada:");
    if (!nimi) return;

    const id = "nimi-" + nimi.toLowerCase().replace(/\s+/g, "-");
    const li = document.getElementById(id);

    if (li) {
        li.remove();
        alert("Eemaldatud: " + nimi);
    } else {
        alert("Nime '" + nimi + "' ei leitud nimekirjas!");
    }
}

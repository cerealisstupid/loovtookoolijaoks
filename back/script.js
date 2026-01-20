async function loadList() {
    const res = await fetch("api/list.php");
    const names = await res.json();

    const ul = document.getElementById("nimekiri");
    ul.innerHTML = "";

    names.forEach(name => {
        const li = document.createElement("li");
        li.textContent = name;
        ul.appendChild(li);
    });
}

document.getElementById("reg").onclick = async () => {
    const nimi = prompt("Sisesta nimi:");
    if (!nimi) return;

    await fetch("api/add.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "name=" + encodeURIComponent(nimi)
    });

    loadList();
};

document.getElementById("unreg").onclick = async () => {
    const nimi = prompt("Sisesta nimi, mida eemaldada:");
    if (!nimi) return;

    await fetch("api/remove.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "name=" + encodeURIComponent(nimi)
    });

    loadList();
};

loadList();

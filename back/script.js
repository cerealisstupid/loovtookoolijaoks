function naitaTeade(teade, liik = 'info') {
    const olemasolevTeade = document.querySelector('.teade');
    if (olemasolevTeade) olemasolevTeade.remove();

    const teadeDiv = document.createElement('div');
    teadeDiv.className = `teade ${liik}`;
    teadeDiv.textContent = teade;
    teadeDiv.style.cssText = `
        padding: 10px 20px;
        margin: 10px 0;
        border-radius: 5px;
        font-weight: bold;
        ${liik === 'viga' ? 'background-color: #ffebee; color: #c62828;' : ''}
        ${liik === 'edukas' ? 'background-color: #e8f5e9; color: #2e7d32;' : ''}
        ${liik === 'info' ? 'background-color: #e3f2fd; color: #1565c0;' : ''}
    `;
    
    document.querySelector('.content').insertBefore(teadeDiv, document.querySelector('.content').firstChild);
    
    setTimeout(() => teadeDiv.remove(), 5000);
}

function kontrolliNime(nimi) {
    if (!nimi || nimi.trim().length === 0) {
        throw new Error("Nimi ei saa olla tühi!");
    }
    
    if (nimi.trim().length < 2) {
        throw new Error("Nimi peab olema vähemalt 2 tähemärki pikk!");
    }
    
    if (nimi.trim().length > 50) {
        throw new Error("Nimi on liiga pikk (max 50 tähemärki)!");
    }
    
    const nimiMuster = /^[a-zA-ZäöüõÄÖÜÕšžŠŽ\s'-]+$/;
    if (!nimiMuster.test(nimi.trim())) {
        throw new Error("Nimi sisaldab keelatud tähemärke!");
    }
    
    return nimi.trim();
}

async function laadNimekiri() {
    try {
        const vastus = await fetch("api/nimekiri.php");
        
        if (!vastus.ok) {
            throw new Error(`Serveri viga: ${vastus.status}`);
        }
        
        const andmed = await vastus.json();
        
        if (andmed.error) {
            throw new Error(andmed.error);
        }
        
        const nimed = andmed.names || [];
        const nimekiri = document.getElementById("nimekiri");
        nimekiri.innerHTML = "";

        if (nimed.length === 0) {
            const element = document.createElement("li");
            element.textContent = "Keegi pole veel registreerunud";
            element.style.fontStyle = "italic";
            element.style.color = "#666";
            nimekiri.appendChild(element);
        } else {
            nimed.forEach(nimi => {
                const element = document.createElement("li");
                element.textContent = nimi;
                nimekiri.appendChild(element);
            });
        }
        
        const loendus = document.querySelector('.content i:nth-of-type(2)');
        if (loendus) {
            loendus.textContent = `Hetkel registreerunud: ${nimed.length} inimest`;
        }
        
    } catch (viga) {
        console.error("Viga nimekirja laadimisel:", viga);
        naitaTeade("Viga nimekirja laadimisel: " + viga.message, 'viga');
    }
}

document.getElementById("reg").onclick = async () => {
    try {
        const nimi = prompt("Sisesta nimi:");
        if (nimi === null) return;
        
        const kontrollitudNimi = kontrolliNime(nimi);

        const vastus = await fetch("api/lisa.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "name=" + encodeURIComponent(kontrollitudNimi)
        });
        
        if (!vastus.ok) {
            throw new Error(`Serveri viga: ${vastus.status}`);
        }
        
        const andmed = await vastus.json();
        
        if (andmed.error) {
            throw new Error(andmed.error);
        }
        
        naitaTeade(andmed.message || "Registreerimine õnnestus!", 'edukas');
        await laadNimekiri();
        
    } catch (viga) {
        console.error("Registreerimise viga:", viga);
        naitaTeade(viga.message, 'viga');
    }
};

document.getElementById("unreg").onclick = async () => {
    try {
        const nimi = prompt("Sisesta nimi, mida eemaldada:");
        if (nimi === null) return;
        
        const kontrollitudNimi = kontrolliNime(nimi);

        const vastus = await fetch("api/eemalda.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "name=" + encodeURIComponent(kontrollitudNimi)
        });
        
        if (!vastus.ok) {
            throw new Error(`Serveri viga: ${vastus.status}`);
        }
        
        const andmed = await vastus.json();
        
        if (andmed.error) {
            throw new Error(andmed.error);
        }
        
        naitaTeade(andmed.message || "Eemaldamine õnnestus!", 'edukas');
        await laadNimekiri();
        
    } catch (viga) {
        console.error("Eemaldamise viga:", viga);
        naitaTeade(viga.message, 'viga');
    }
};

let saadavalAjad = [];

async function laadSaadavalAjad() {
    try {
        const vastus = await fetch('api/koik_ajad.php');
        const andmed = await vastus.json();
        
        if (andmed.error) {
            throw new Error(andmed.error);
        }
        
        saadavalAjad = andmed.slots || [];
        
        const ajadKlasside = {};
        saadavalAjad.forEach(aeg => {
            if (!ajadKlasside[aeg.class]) {
                ajadKlasside[aeg.class] = [];
            }
            ajadKlasside[aeg.class].push(aeg);
        });
        
        const klassiValik = document.getElementById("classSelect");
        klassiValik.innerHTML = '<option value="">Vali klass</option>';
        
        Object.keys(ajadKlasside).sort().forEach(klassiNimi => {
            const valik = document.createElement("option");
            valik.value = klassiNimi;
            valik.textContent = `${klassiNimi}. klass`;
            klassiValik.appendChild(valik);
        });
        
    } catch (viga) {
        console.error("Viga aegu laadides:", viga);
        naitaTeade("Viga konsultatsioonide laadimisel: " + viga.message, 'viga');
    }
}

const klassiValik = document.getElementById("classSelect");
klassiValik.addEventListener("change", () => {
    const aegValik = document.getElementById("slotSelect");
    aegValik.innerHTML = '<option value="">Vali aeg / õpetaja / aine</option>';
    
    const valitudKlass = klassiValik.value;
    if (!valitudKlass) return;
    
    const ajad = saadavalAjad.filter(aeg => aeg.class === valitudKlass);
    
    if (ajad.length === 0) {
        const valik = document.createElement("option");
        valik.value = "";
        valik.textContent = "Sellel klassi pole konsultatsioone";
        aegValik.appendChild(valik);
        return;
    }
    
    ajad.forEach(aeg => {
        const registreeringuLoendus = aeg.registrations ? aeg.registrations.length : 0;
        const onTais = registreeringuLoendus >= aeg.maxStudents;
        
        const valik = document.createElement("option");
        valik.value = aeg.id;
        valik.textContent = `${aeg.date} ${aeg.timeStart}-${aeg.timeEnd} - ${aeg.teacher} (${aeg.subject})`;
        
        if (onTais) {
            valik.textContent += ' [TÄIS]';
            valik.disabled = true;
        } else {
            valik.textContent += ` [${registreeringuLoendus}/${aeg.maxStudents}]`;
        }
        
        aegValik.appendChild(valik);
    });
});

document.getElementById("regSlot").onclick = async () => {
    try {
        const aegId = document.getElementById("slotSelect").value;
        if (!aegId) {
            throw new Error("Palun vali konsultatsiooni aeg!");
        }
        
        const opilaseNimi = prompt("Sisesta õpilase nimi:");
        if (opilaseNimi === null) return;
        
        const kontrollitudNimi = kontrolliNime(opilaseNimi);
        
        const vastus = await fetch("api/lisa_konsultatsioon.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                slotId: parseInt(aegId),
                studentName: kontrollitudNimi
            })
        });
        
        if (!vastus.ok) {
            throw new Error(`Serveri viga: ${vastus.status}`);
        }
        
        const andmed = await vastus.json();
        
        if (andmed.error) {
            throw new Error(andmed.error);
        }
        
        naitaTeade(andmed.message || "Konsultatsioonile registreerimine õnnestus!", 'edukas');
        await laadSaadavalAjad();
        await laadKonsultatsioonid();
        
        document.getElementById("classSelect").value = "";
        document.getElementById("slotSelect").innerHTML = '<option value="">Vali aeg / õpetaja / aine</option>';
        
    } catch (viga) {
        console.error("Konsultatsiooni registreerimise viga:", viga);
        naitaTeade(viga.message, 'viga');
    }
};

document.getElementById("unregSlot").onclick = async () => {
    try {
        const opilaseNimi = prompt("Sisesta õpilase nimi:");
        if (opilaseNimi === null) return;
        
        const kontrollitudNimi = kontrolliNime(opilaseNimi);
        
        const vastus = await fetch("api/eemalda_konsultatsioon.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                studentName: kontrollitudNimi
            })
        });
        
        if (!vastus.ok) {
            throw new Error(`Serveri viga: ${vastus.status}`);
        }
        
        const andmed = await vastus.json();
        
        if (andmed.error) {
            throw new Error(andmed.error);
        }
        
        naitaTeade(andmed.message || "Registreering eemaldatud!", 'edukas');
        await laadSaadavalAjad();
        await laadKonsultatsioonid();
        
    } catch (viga) {
        console.error("Konsultatsiooni eemaldamise viga:", viga);
        naitaTeade(viga.message, 'viga');
    }
};

async function laadKonsultatsioonid() {
    try {
        const vastus = await fetch("api/konsultatsioonide_nimekiri.php");
        
        if (!vastus.ok) {
            throw new Error(`Serveri viga: ${vastus.status}`);
        }
        
        const andmed = await vastus.json();
        
        if (andmed.error) {
            throw new Error(andmed.error);
        }
        
        const registreeringud = andmed.registrations || [];
        const nimekiri = document.getElementById("slotList");
        nimekiri.innerHTML = "";
        
        if (registreeringud.length === 0) {
            const element = document.createElement("li");
            element.textContent = "Konsultatsioonidele pole veel registreerunud";
            element.style.fontStyle = "italic";
            element.style.color = "#666";
            nimekiri.appendChild(element);
        } else {
            registreeringud.forEach(reg => {
                const element = document.createElement("li");
                element.textContent = `${reg.studentName} - ${reg.date} ${reg.time} (${reg.teacher}, ${reg.subject})`;
                nimekiri.appendChild(element);
            });
        }
        
    } catch (viga) {
        console.error("Viga konsultatsioonide laadimisel:", viga);
        naitaTeade("Viga konsultatsioonide laadimisel: " + viga.message, 'viga');
    }
}

laadNimekiri();
laadSaadavalAjad();
laadKonsultatsioonid();

document.addEventListener("DOMContentLoaded", function() {
    const addButton = document.querySelector("button:nth-of-type(1)");
    const table = document.querySelector("table");

    const disableInputs = (inputs) => inputs.forEach(input => input.disabled = true);
    const enableInputs = (inputs) => inputs.forEach(input => input.disabled = false);

    const createActionButton = (text, callback) => {
        const button = document.createElement("button");
        button.textContent = text;
        button.addEventListener("click", callback);
        return button;
    };

    addButton.addEventListener("click", function() {
        const newRow = table.insertRow(-1);
        const fields = ["text", "text", "text", "text", "select", "select", "select", "date", "action"];
        fields.forEach((type, index) => {
            const cell = newRow.insertCell(index);
            if (type === "select") {
                const select = document.createElement("select");
                const options = index === 4 ? ["DIT", "DOMT", "BSIT", "BSECE", "BSME", "BSED-MATH", "BSED-ENGLISH"] :
                               index === 5 ? ["1st", "2nd", "3rd", "4th", "Ladderized"] : ["No ID"];
                select.innerHTML = `<option disabled selected hidden>Select</option>` + options.map(opt => `<option>${opt}</option>`).join("");
                cell.appendChild(select);
            } else if (type === "action") {
                const edit = createActionButton("Edit", () => enableInputs(Array.from(newRow.querySelectorAll("input, select"))));
                const save = createActionButton("Save", () => {
                    const inputs = Array.from(newRow.querySelectorAll("input, select"));
                    const studentData = new FormData();

                    studentData.append("studentNumber", inputs[0].value);
                    studentData.append("lastName", inputs[1].value);
                    studentData.append("firstName", inputs[2].value);
                    studentData.append("middleName", inputs[3].value);
                    studentData.append("program", inputs[4].value);
                    studentData.append("yearLevel", inputs[5].value);
                    studentData.append("violationType", inputs[6].value);
                    studentData.append("date", inputs[7].value);

                    // Send the data via AJAX to the PHP script
                    fetch("id_violation.php", {
                        method: "POST",
                        body: studentData
                    })
                    .then(response => response.text())
                    .then(data => {
                        alert(data); // Show response message from server
                        disableInputs(inputs); // Disable the inputs after saving
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        alert("Failed to save data!");
                    });
                });
                const del = createActionButton("Delete", () => {
                    if (confirm("Are you sure you want to delete this entry?")) {
                        newRow.remove();
                    }
                });
                [edit, save, del].forEach(btn => cell.appendChild(btn));
            } else {
                const input = document.createElement("input");
                input.type = type;
                cell.appendChild(input);
            }
        });
    });
});

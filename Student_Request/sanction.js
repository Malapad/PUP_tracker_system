// Sanctions data
const sanctions = [
  { id: 1, violation: "Not wearing ID", date: "January 25, 2025", canRequest: true },
  { id: 2, violation: "Not wearing school uniform", date: "January 23, 2025", canRequest: true }
];

const sanctionsList = document.getElementById("sanctions-list");

function renderSanctions() {
  sanctions.forEach(sanction => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${sanction.violation}</td>
      <td>${sanction.date}</td>
      <td>
        ${sanction.canRequest
          ? `<button class="action-btn" onclick="requestClearance(${sanction.id})">Request Clearance</button>`
          : `<span>No Action</span>`
        }
      </td>
    `;
    sanctionsList.appendChild(row);
  });
}

function requestClearance(id) {
  alert(`Request for clearance has been sent for sanction ID: ${id}`);
}

renderSanctions();

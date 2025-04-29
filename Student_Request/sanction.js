// Example student sanctions data
const sanctions = [
    { id: 1, type: "Dress Code Violation", status: "Active", reason: "Wore shorts", canRequest: true },
    { id: 2, type: "Tardiness", status: "Cleared", reason: "Late 3 times", canRequest: false }
  ];
  
  const sanctionsList = document.getElementById('sanctions-list');
  
  function renderSanctions() {
    sanctions.forEach(sanction => {
      const container = document.createElement('div');
      container.innerHTML = `
        <p><strong>Type:</strong> ${sanction.type}</p>
        <p><strong>Status:</strong> ${sanction.status}</p>
        <p><strong>Reason:</strong> ${sanction.reason}</p>
        ${sanction.canRequest 
          ? `<button onclick="requestClearance(${sanction.id})">Request Clearance</button>` 
          : `<p><em>No action available</em></p>`
        }
        <hr>
      `;
      sanctionsList.appendChild(container);
    });
  }
  
  function requestClearance(sanctionId) {
    // You would send this to the backend in a real app
    alert(`Clearance request sent for sanction ID: ${sanctionId}`);
  }
  
  renderSanctions();
  
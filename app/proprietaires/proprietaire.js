const canvas = document.getElementById("revenueChart");

if (canvas) {
    const ctx = canvas.getContext("2d");

    new Chart(ctx, {
        type: "line",
        data: {
            labels: window.chartLabels || [],
            datasets: [{
                label: "Revenus encaissés",
                data: window.chartDatas || [],
                borderColor: "#0F8B8D",
                backgroundColor: "rgba(15,139,141,0.15)",
                fill: true,
                tension: 0.35,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

const searchInput=document.getElementById("searchInput");

if(searchInput){

searchInput.addEventListener("keyup",function(){

const value=this.value.toLowerCase();

document.querySelectorAll(".property-card").forEach(card=>{

const text=card.innerText.toLowerCase();

card.style.display=text.includes(value)?"block":"none";

});

});

}
const ctx=document.getElementById("revenueChart");

new Chart(ctx,{
type:"line",

data:{
labels:window.chartLabels,
datasets:[{
label:"Revenus",
data:window.chartDatas,
borderColor:"#0F8B8D",
backgroundColor:"rgba(15,139,141,.15)",
fill:true,
tension:.4,
pointBackgroundColor:"#0F8B8D"
}]
},

options:{
responsive:true,
maintainAspectRatio:false,
plugins:{
legend:{
display:false
}
}
}
});

ctx.parentElement.style.height="300px";
function swapDestination(start, end) {
    let startSelect = document.getElementById(start);
    let endSelect = document.getElementById(end);

    let startValue = startSelect.value;
    let endValue = endSelect.value;

    startSelect.value = endValue;
    endSelect.value = startValue;
}
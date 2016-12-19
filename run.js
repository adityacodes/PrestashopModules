flatpickr(".flatpickr", {
    minDate: new Date(), // "today" / "2016-12-20" / 1477673788975
    maxDate: "2016-12-20",
    enableTime: true,

    // create an extra input solely for display purposes
    altInput: true,
    altFormat: "F j, Y h:i K"
});
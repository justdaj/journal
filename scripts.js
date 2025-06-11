function resizeTextarea(textarea) {
    textarea.style.height = 'auto'; // Reset height to allow shrinking
    textarea.style.height = textarea.scrollHeight + 'px'; // Expand to fit content
}

document.addEventListener('DOMContentLoaded', function() {
    var textareas = document.querySelectorAll('textarea');
    textareas.forEach(function(textarea) {
        resizeTextarea(textarea); // Initial sizing

        // ✅ Add this to make it grow as user types
        textarea.addEventListener('input', function() {
            resizeTextarea(textarea);
        });
    });
});

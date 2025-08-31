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

window.startEdit = function (btn) {
  const id      = btn.dataset.id || '';
  const content = btn.dataset.content || '';
  const mood    = btn.dataset.mood || '😏 Neutral';
  const tagsCsv = btn.dataset.tags || '';

  const form = document.getElementById('entry-form');
  form.querySelector('#entry-id').value = id;
  form.querySelector('#content').value  = content;
  form.querySelector('#mood').value     = mood;
  form.querySelector('#tags').value     = tagsCsv
    .split(',')
    .map(s => s.trim())
    .filter(Boolean)
    .join(', ');

  const saveBtn = form.querySelector('button[name="save_entry"]');
  if (saveBtn) saveBtn.textContent = 'Update entry';

  document.getElementById('content').focus();
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

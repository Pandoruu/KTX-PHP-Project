    </main>
</div>

<script>
// Live clock
function updateTime() {
    const now = new Date();
    const opts = { weekday:'long', year:'numeric', month:'long', day:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit' };
    const el = document.getElementById('live-time');
    if (el) el.textContent = now.toLocaleString('vi-VN', opts);
}
updateTime();
setInterval(updateTime, 1000);

// Modal helpers
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('show'); });
});

// Confirm delete
function confirmDelete(url, name) {
    if (confirm('⚠️ Bạn có chắc muốn xóa "' + name + '"?\nHành động này không thể hoàn tác!')) {
        window.location.href = url;
    }
}

// Auto dismiss alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        a.style.transition = 'opacity .5s';
        a.style.opacity = '0';
        setTimeout(() => a.remove(), 500);
    });
}, 4000);
</script>
</body>
</html>

document.querySelector('.btn-new').onclick = function() {
        document.getElementById('addProductModal').style.display = "block";
    }
    function closeModal() {
        document.getElementById('addProductModal').style.display = "none";
    }

 function toggleSidebar() {
      const sidebar = document.getElementById('leftside');
      const main = document.querySelector('main');
      sidebar.classList.toggle('hidden');
      main.classList.toggle('full-width');
    }

    function toggleMenu(event) {
      event.preventDefault();
      const submenu = event.target.nextElementSibling;
      if (submenu && submenu.classList.contains('menuleft_hide')) {
        const isVisible = submenu.style.display === 'block';
        submenu.style.display = isVisible ? 'none' : 'block';
      }
    }



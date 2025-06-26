// Fonction de validation pour la page de connexion
function validateLoginForm() {
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();

  // Vérifier que l'email et le mot de passe sont remplis
  if (!email || !password) {
    alert("Veuillez remplir tous les champs.");
    return false;
  }

  // Vérifier que l'email est valide
  if (!validateEmail(email)) {
    alert("Veuillez entrer une adresse email valide.");
    return false;
  }

  return true; // Si tout est valide, le formulaire est soumis
}

// Validation côté client
function validateRegisterForm() {
  const username = document.getElementById("username").value.trim();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;
  const confirmPassword = document.getElementById("confirm_password").value;

  if (!username || !email || !password || !confirmPassword) {
    alert("Veuillez remplir tous les champs.");
    return false;
  }

  if (!validateEmail(email)) {
    alert("Veuillez entrer une adresse email valide.");
    return false;
  }

  if (username.length < 3) {
    alert("Le nom d'utilisateur doit contenir au moins 3 caractères.");
    return false;
  }

  if (password.length < 6) {
    alert("Le mot de passe doit contenir au moins 6 caractères.");
    return false;
  }

  if (password !== confirmPassword) {
    alert("Les mots de passe ne correspondent pas.");
    return false;
  }

  return true;
}

// Fonction pour valider l'email
function validateEmail(email) {
  const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/i;
  return re.test(email);
}

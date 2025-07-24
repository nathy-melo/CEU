<!DOCTYPE html>
<html lang="pt-br">

/* preciso modificar */

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Eventos</title>
    <link rel="stylesheet" href="../styleGlobal.css" />
    <style>
       :root {
  --bg-page: #d1eaff;
  --bg-card: #4f6c8c;
  --bg-input: #ffffff;
  --bg-button-secondary: #6598d2;
  --text-light: #ffffff;
  --text-dark: #000000;
  --shadow-main: 0px 4px 20px 0px rgba(0, 0, 0, 0.6);
}

*,
*::before,
*::after {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: 'Inter', sans-serif;
  background-color: var(--bg-page);
  color: var(--text-dark);
}

.form-page-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 40px 20px;
}

.form-container {
  background-color: var(--bg-card);
  border-radius: 16px;
  box-shadow: var(--shadow-main);
  padding: 43px 60px 60px;
  width: 100%;
  max-width: 1375px;
  overflow: hidden;
}

.form-title {
  color: var(--text-light);
  font-weight: 700;
  font-size: 36px;
  text-align: center;
  margin: 0 0 28px 0;
}

.form-content {
  display: flex;
  flex-direction: column;
  gap: 64px;
}

.form-row {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
}

.form-row > .form-group,
.form-row > .details-column,
.form-row > .description-group {
  flex: 1 1 0;
  min-width: 250px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

label {
  color: var(--text-light);
  font-weight: 700;
  font-size: 36px;
  line-height: 1.3;
}

.label-with-shadow {
  text-shadow: var(--shadow-main);
}

input,
textarea,
select {
  background-color: var(--bg-input);
  border-radius: 3px;
  border: none;
  padding: 12px 16px;
  font-family: 'Inter', sans-serif;
  font-size: 20px;
  color: var(--text-dark);
  width: 100%;
  height: 54px;
}

select {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  background-size: 1em;
  padding-right: 2.5rem;
}

textarea {
  height: auto;
  min-height: 412px;
  resize: vertical;
  padding-top: 12px;
}

.description-group {
  display: flex;
  flex-direction: column;
  gap: 20px;
  flex-basis: 50%;
}

.image-upload-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  background: none;
  border: none;
  color: var(--text-light);
  font-weight: 700;
  font-size: 36px;
  cursor: pointer;
  padding: 0;
}

.image-upload-btn img {
  width: 40px;
  height: 40px;
}

.details-column {
  display: flex;
  flex-direction: column;
  gap: 48px;
  flex-basis: 40%;
}

.btn {
  font-family: 'Inter', sans-serif;
  font-weight: 700;
  border: none;
  cursor: pointer;
  text-align: center;
  padding: 18px;
  font-size: 24px;
  border-radius: 6px;
}

.btn-participants {
  background-color: var(--bg-input);
  color: var(--text-dark);
  height: 67px;
}

.form-actions {
  display: flex;
  justify-content: center;
  margin-top: 40px;
}

.btn-back {
  background-color: var(--bg-button-secondary);
  color: var(--text-light);
  box-shadow: var(--shadow-main);
  width: 300px;
  height: 67px;
}

<section id="edit-form">
  <div class="form-page-wrapper">
    <div class="form-container">
      <form>
        <h1 class="form-title">Editar Evento</h1>

        <div class="form-content">
          <div class="form-row">
            <div class="form-group">
              <label for="event-name">Nome:</label>
              <input type="text" id="event-name" name="event-name" value="Evento X">
            </div>
            <div class="form-group">
              <label for="event-location">Local:</label>
              <input type="text" id="event-location" name="event-location" value="Auditório">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="start-date">Data de início:</label>
              <input type="text" id="start-date" name="start-date" value="07/03/25">
            </div>
            <div class="form-group">
              <label for="end-date">Data de fim:</label>
              <input type="text" id="end-date" name="end-date" value="07/03/25">
            </div>
            <div class="form-group">
              <label for="start-time">Horário início:</label>
              <input type="text" id="start-time" name="start-time" value="13:00">
            </div>
            <div class="form-group">
              <label for="end-time">Horário fim:</label>
              <input type="text" id="end-time" name="end-time" value="14:00">
            </div>
          </div>

          <div class="form-row form-row-complex">
            <div class="form-group description-group">
              <label for="event-description">Descrição:</label>
              <textarea id="event-description" name="event-description">Descrição</textarea>
              <button type="button" class="image-upload-btn">
                <span>Adicionar imagem</span>
                <img src="/page/13a06413-e979-4c45-b93c-0abe8bf0ecb1/images/733_4011.svg" alt="Upload icon">
              </button>
            </div>
            <div class="details-column">
              <div class="form-group">
                <label for="target-audience">Público alvo:</label>
                <input type="text" id="target-audience" name="target-audience" value="Estudantes">
              </div>
              <div class="form-group">
                <label for="category">Categoria:</label>
                <input type="text" id="category" name="category" value="Oficina">
              </div>
              <div class="form-group">
                <label for="cert-type" class="label-with-shadow">Tipo de certificado:</label>
                <select id="cert-type" name="cert-type">
                  <option>Selecionar</option>
                </select>
              </div>
              <div class="form-group">
                <label for="cert-model">Modelo do certificado:</label>
                <select id="cert-model" name="cert-model">
                  <option>Selecionar</option>
                </select>
              </div>
              <button type="button" class="btn btn-participants">Lista de Participantes</button>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-back">Voltar</button>
        </div>
      </form>
    </div>
  </div>
</section>
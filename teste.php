
                    <!-- Departamento -->
                        <div class="col-md-6">
                        <label class="form-label">Setor <span class="text-danger">*</span></label>
                        <input type="text" name="department" class="form-control" required
                               value="<?= htmlspecialchars($_POST['department'] ?? '') ?>">
                        <div class="invalid-feedback">
                            Informe o Setor
                        </div>
                    </div> 

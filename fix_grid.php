<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/super_admin.php");

$bad = <<<PHP
                <button type="submit" name="create_tenant" class="btn" style="background:#0ea5e9; color:white; width:100%; padding:0.75rem;">Create Account (1 Mo Free)</button>
            </form>
        </div>
        <div class="card" style="margin-top: 1.5rem;">
            <h3>Update Super Admin Credentials</h3>
PHP;

$good = <<<PHP
                <button type="submit" name="create_tenant" class="btn" style="background:#0ea5e9; color:white; width:100%; padding:0.75rem;">Create Account (1 Mo Free)</button>
            </form>
        </div>
        
        <div> <!-- Start of Right Column Wrapper -->
        <div class="card">
            <h3>Update Super Admin Credentials</h3>
PHP;

$f = str_replace($bad, $good, $f);

$bad2 = <<<PHP
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
PHP;

$good2 = <<<PHP
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        </div> <!-- End of Right Column Wrapper -->
    </div>
</div>
</body>
</html>
PHP;

$f = str_replace($bad2, $good2, $f);

file_put_contents("C:/xampp/htdocs/point of sale/super_admin.php", $f);
echo "Fixed layout grid.\n";
?>

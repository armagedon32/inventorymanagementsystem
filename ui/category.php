<?php
// ====================================
// category.php - Full Item & Category Management
// ====================================

include_once 'connectdb.php';
session_start();

// =================== SESSION CHECK ===================
if(!isset($_SESSION['role'])){
    header("Location: ../index.php");
    exit();
}

// =================== CATEGORY & ITEM HANDLERS ===================
$categoryEditId = null;
$itemEditId = null;

// ===== ADD / UPDATE CATEGORY =====
if(isset($_POST['btnAddUpdateCategory'])){
    $categoryId = !empty($_POST['txtCategoryID']) ? $_POST['txtCategoryID'] : null;
    $categoryName = trim($_POST['txtCategoryName']);

    if(!empty($categoryName)){
        // Prevent duplicate category names
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM tbl_category WHERE category=:cat AND (:id IS NULL OR catid != :id)");
        $stmtCheck->execute([':cat'=>$categoryName, ':id'=>$categoryId]);
        if($stmtCheck->fetchColumn() > 0){
            $_SESSION['status']="Category already exists";
            $_SESSION['status_code']="warning";
        } else {
            if($categoryId){
                $stmt = $pdo->prepare("UPDATE tbl_category SET category=:cat WHERE catid=:id");
                $stmt->execute([':cat'=>$categoryName, ':id'=>$categoryId]);
                
                // Log activity
                $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
                $stmtLog->execute([$_SESSION['userid'], "Updated Category: " . $categoryName]);

                $_SESSION['status']="Category Updated Successfully";
                $_SESSION['status_code']="success";
            } else {
                $stmt = $pdo->prepare("INSERT INTO tbl_category (category) VALUES(:cat)");
                $stmt->execute([':cat'=>$categoryName]);

                // Log activity
                $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
                $stmtLog->execute([$_SESSION['userid'], "Added New Category: " . $categoryName]);

                $_SESSION['status']="Category Added Successfully";
                $_SESSION['status_code']="success";
            }
        }
    } else {
        $_SESSION['status']="Category Name cannot be empty";
        $_SESSION['status_code']="warning";
        $categoryEditId = $categoryId;
        header("Location: category.php?editCategory=$categoryEditId");
        exit();
    }
    header("Location: category.php");
    exit();
}

// ===== DELETE CATEGORY =====
if(isset($_POST['btnDeleteCategory'])){
    $catid = $_POST['btnDeleteCategory'];

    // Fetch name for logging
    $stmtName = $pdo->prepare("SELECT category FROM tbl_category WHERE catid=:id");
    $stmtName->execute([':id'=>$catid]);
    $catName = $stmtName->fetchColumn();

    // Delete all items under this category
    $stmtItems = $pdo->prepare("DELETE FROM tbl_item WHERE category_id=:id");
    $stmtItems->execute([':id'=>$catid]);

    // Delete the category itself
    $stmt = $pdo->prepare("DELETE FROM tbl_category WHERE catid=:id");
    $stmt->execute([':id'=>$catid]);

    // Log activity
    $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
    $stmtLog->execute([$_SESSION['userid'], "Deleted Category: " . $catName]);

    $_SESSION['status']="Category and its items deleted successfully";
    $_SESSION['status_code']="success";
    header("Location: category.php");
    exit();
}

// ===== ADD / UPDATE ITEM =====
if(isset($_POST['btnAddUpdateItem'])){
    $itemId = !empty($_POST['txtItemID']) ? $_POST['txtItemID'] : null;
    $itemName = trim($_POST['txtNewItemName']);
    $categoryId = $_POST['txtItemCategory'];

    if(!empty($itemName) && !empty($categoryId)){
        // Prevent duplicate item names
        $stmtCheck = $pdo->prepare("SELECT i.*, c.category FROM tbl_item i JOIN tbl_category c ON i.category_id = c.catid WHERE i.item_name=:iname AND (:id IS NULL OR i.itemid != :id)");
        $stmtCheck->execute([':iname'=>$itemName, ':id'=>$itemId]);
        $existingItem = $stmtCheck->fetch(PDO::FETCH_OBJ);

        if($existingItem){
            $_SESSION['status']="Item '{$itemName}' already exists in category: " . $existingItem->category;
            $_SESSION['status_code']="warning";
            if($itemId){
                header("Location: category.php?editItem=$itemId");
                exit();
            }
        } else {
            if($itemId){
                $stmt = $pdo->prepare("UPDATE tbl_item SET item_name=:iname, category_id=:catid WHERE itemid=:id");
                $stmt->execute([':iname'=>$itemName, ':catid'=>$categoryId, ':id'=>$itemId]);

                // Log activity
                $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
                $stmtLog->execute([$_SESSION['userid'], "Updated Item: " . $itemName]);

                $_SESSION['status']="Item Updated Successfully";
                $_SESSION['status_code']="success";
            } else {
                $stmt = $pdo->prepare("INSERT INTO tbl_item (item_name, category_id) VALUES(:iname, :catid)");
                $stmt->execute([':iname'=>$itemName, ':catid'=>$categoryId]);

                // Log activity
                $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
                $stmtLog->execute([$_SESSION['userid'], "Added New Item: " . $itemName]);

                $_SESSION['status']="Item Added Successfully";
                $_SESSION['status_code']="success";
            }
        }
    } else {
        $_SESSION['status']="Item Name and Category cannot be empty";
        $_SESSION['status_code']="warning";
        $itemEditId = $itemId;
        header("Location: category.php?editItem=$itemEditId");
        exit();
    }
    header("Location: category.php");
    exit();
}

// ===== DELETE ITEM =====
if(isset($_POST['btndelete_item'])){
    $itemId = $_POST['btndelete_item'];

    // Fetch name for logging
    $stmtName = $pdo->prepare("SELECT item_name FROM tbl_item WHERE itemid=:id");
    $stmtName->execute([':id'=>$itemId]);
    $itemName = $stmtName->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM tbl_item WHERE itemid=:id");
    $stmt->execute([':id'=>$itemId]);

    // Log activity
    $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
    $stmtLog->execute([$_SESSION['userid'], "Deleted Item: " . $itemName]);

    $_SESSION['status']="Item Deleted Successfully";
    $_SESSION['status_code']="success";
    header("Location: category.php");
    exit();
}

// =================== INCLUDE HEADER ===================
if($_SESSION['role']=="Admin"){
    include_once "header.php";
}else{
    include_once "headeruser.php";
}

// =================== FETCH CATEGORIES & ITEMS ===================
$categories = $pdo->query("SELECT * FROM tbl_category WHERE is_archived = 0 ORDER BY catid ASC")->fetchAll(PDO::FETCH_OBJ);

// ===== SEARCH & FILTER LOGIC =====
$where_clauses = ["i.is_archived = 0"];
$params = [];

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = "%" . trim($_GET['search']) . "%";
    $where_clauses[] = "i.item_name LIKE :search";
    $params[':search'] = $search;
}

if (isset($_GET['filter_cat']) && $_GET['filter_cat'] != 'All') {
    $where_clauses[] = "i.category_id = :cat_id";
    $params[':cat_id'] = $_GET['filter_cat'];
}

$query = "SELECT i.*, c.category FROM tbl_item i LEFT JOIN tbl_category c ON i.category_id=c.catid";
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}
$query .= " ORDER BY i.itemid ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_OBJ);

// ===== CHECK FOR EDIT QUERIES =====
if(isset($_GET['editCategory'])) $categoryEditId = $_GET['editCategory'];
if(isset($_GET['editItem'])) $itemEditId = $_GET['editItem'];
?>

<div class="d-flex flex-column min-vh-100">
<div class="content-wrapper flex-grow-1">
  <div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h4 class="m-0 text-dark">
                    Category & Item Management
                </h4>
            </div>
        </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <!-- SEARCH & FILTER -->
      <div class="card card-outline card-primary shadow-sm mb-4">
        <div class="card-body">
          <form method="get" id="filterForm">
            <div class="row align-items-end">
              <div class="col-md-4">
                <div class="form-group mb-md-0">
                  <label class="small text-muted mb-1"><i class="fas fa-filter mr-1"></i> Filter by Category</label>
                  <select name="filter_cat" id="filter_cat" class="form-control form-control-lg">
                    <option value="All">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat->catid ?>" <?= (isset($_GET['filter_cat']) && $_GET['filter_cat'] == $cat->catid) ? 'selected' : '' ?>><?= htmlspecialchars($cat->category) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-8">
                <div class="form-group mb-md-0">
                  <label class="small text-muted mb-1"><i class="fas fa-search mr-1"></i> Search Item</label>
                  <div class="input-group input-group-lg">
                    <input type="text" name="search" id="search" class="form-control" placeholder="Search by item name" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" autocomplete="off">
                    <div class="input-group-append">
                      <button class="btn btn-info px-4" type="submit">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- ITEM TABLE -->
      <div class="card card-outline card-success shadow-sm mt-3">
        <div class="card-header border-0 pt-3">
          <h5 class="mb-0 text-dark font-weight-bold"><i class="fas fa-list mr-2"></i>All Items</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0" id="itemTable">
              <thead class="bg-light">
                <tr>
                  <th class="pl-4 py-3">#</th>
                  <th class="py-3">Item Name</th>
                  <th class="py-3">Category</th>
                  <th class="py-3 text-center">Actions</th>
                </tr>
              </thead>
              <tbody id="itemTableBody">
                <?php foreach($items as $item): ?>
                <tr>
                  <td class="pl-4 py-3 align-middle"><?= $item->itemid ?></td>
                  <td class="py-3 align-middle text-dark font-weight-bold"><?= htmlspecialchars($item->item_name) ?></td>
                  <td class="py-3 align-middle">
                    <span class="badge badge-info px-3 py-2"><?= htmlspecialchars($item->category) ?></span>
                  </td>
                  <td class="py-3 align-middle text-center">
                    <button type="button" class="btn btn-primary btn-sm btn-update-item mr-1" 
                        data-id="<?= $item->itemid ?>" 
                        data-name="<?= htmlspecialchars($item->item_name) ?>" 
                        data-cat="<?= $item->category_id ?>">
                        <i class="fas fa-edit"></i>
                    </button>

                    <form method="post" style="display:inline;" class="delete-item-form">
                        <input type="hidden" name="btndelete_item" value="<?= $item->itemid ?>">
                        <button type="button" class="btn btn-danger btn-sm btn-confirm-delete-item">
                          <i class="fas fa-trash"></i>
                        </button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($items)): ?>
                <tr>
                  <td colspan="4" class="text-center py-5 text-muted">No items found matching your criteria.</td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- FLOATING ADD ITEM BUTTON -->
<button type="button" class="btn btn-primary btn-lg shadow-lg" 
        style="position:fixed; bottom:80px; right:20px; width:60px; height:60px; border-radius: 50%; z-index: 1000;" 
        data-bs-toggle="modal" data-bs-target="#addItemModal">
  <i class="fas fa-plus"></i>
</button>

<!-- CATEGORY MODAL -->
<div class="modal fade" id="categoryListModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      <form method="post" id="categoryForm">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title font-weight-bold"><i class="fas fa-tags mr-2"></i>Categories</h5>
          <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="txtCategoryID" id="txtCategoryID" value="<?= $categoryEditId ?? '' ?>">
            <div class="form-group mb-4">
              <label class="small text-muted mb-1">Category Name</label>
              <div class="input-group">
                <input type="text" class="form-control" name="txtCategoryName" id="txtCategoryName" placeholder="Enter category name" required 
                       value="<?php
                          if($categoryEditId){
                              $catObj = array_filter($categories, fn($c)=>$c->catid==$categoryEditId);
                              if($catObj) echo reset($catObj)->category;
                          }
                       ?>">
                <div class="input-group-append">
                  <button class="btn btn-success" type="submit" name="btnAddUpdateCategory">Save</button>
                </div>
              </div>
            </div>

           <!-- CATEGORY LIST -->
           <label class="small text-muted mb-2">Existing Categories</label>
           <div class="list-group shadow-sm">
              <?php foreach ($categories as $cat): ?>
                  <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                      <span class="text-dark font-weight-bold"><?= htmlspecialchars($cat->category) ?></span>
                      <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary btn-sm btn-edit-category" data-id="<?= $cat->catid ?>" data-name="<?= htmlspecialchars($cat->category) ?>">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-confirm-delete" data-id="<?= $cat->catid ?>">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                  </div>
              <?php endforeach; ?>
           </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ITEM MODAL -->
<div class="modal fade" id="addItemModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content shadow-lg border-0">
      <form method="post" id="itemForm">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title font-weight-bold"><i class="fas fa-box mr-2"></i>Item Details</h5>
          <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="txtItemID" id="txtItemID" value="<?= $itemEditId ?? '' ?>">
          <div class="form-group mb-3">
            <label class="small text-muted mb-1">Item Name</label>
            <input type="text" name="txtNewItemName" class="form-control" id="txtNewItemName" placeholder="Enter item name" required
                   value="<?php
                        if($itemEditId){
                            $itemObj = array_filter($items, fn($i)=>$i->itemid==$itemEditId);
                            if($itemObj) echo reset($itemObj)->item_name;
                        }
                   ?>">
          </div>
          <div class="form-group mb-3">
            <label class="small text-muted mb-1">Category</label>
            <div class="input-group">
              <select name="txtItemCategory" id="txtItemCategory" class="form-control" required>
                <option value="">-- Select Category --</option>
                <?php foreach($categories as $cat) {
                    $selected = ($itemEditId && reset(array_filter($items, fn($i)=>$i->itemid==$itemEditId))->category_id==$cat->catid) ? "selected" : "";
                    echo "<option value='{$cat->catid}' {$selected}>{$cat->category}</option>";
                } ?>
              </select>
              <div class="input-group-append">
                <button type="button" class="btn btn-primary" id="btnAddCategoryFromItemModal" data-bs-toggle="modal" data-bs-target="#categoryListModal">
                  <i class="fas fa-plus"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="submit" name="btnAddUpdateItem" class="btn btn-success px-4">Save Item</button>
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include_once "footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function(){

    // ===== Real-time AJAX Filtering =====
    function filterItems() {
        const formData = $('#filterForm').serialize();
        const url = 'category.php?' + formData;

        // Visual feedback
        $('#itemTableBody').css('opacity', '0.5');

        // Update URL without reload
        window.history.pushState({}, '', url);

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                const newTableBody = $(response).find('#itemTableBody').html();
                $('#itemTableBody').html(newTableBody);
                $('#itemTableBody').css('opacity', '1');
            },
            error: function() {
                $('#itemTableBody').css('opacity', '1');
            }
        });
    }

    // Trigger on category change
    $('#filter_cat').on('change', function() {
        filterItems();
    });

    // Trigger on search input with debounce
    let searchTimeout;
    $('#search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterItems, 300);
    });

    // Prevent default form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        filterItems();
    });

    // ===== SweetAlert Notifications =====
    <?php if(isset($_SESSION['status'])): ?>
        Swal.fire({
            icon: <?= json_encode($_SESSION['status_code']) ?>,
            title: <?= json_encode(($_SESSION['status_code'] == 'success') ? "Success!" : "Notice") ?>,
            text: <?= json_encode($_SESSION['status']) ?>,
            <?php if($_SESSION['status_code'] == 'success'): ?>
                timer: 1800,
                showConfirmButton: false
            <?php else: ?>
                showConfirmButton: true,
                confirmButtonColor: '#007bff'
            <?php endif; ?>
        });
        <?php unset($_SESSION['status'], $_SESSION['status_code']); ?>
    <?php endif; ?>

    // Delete item confirmation
    $(document).on('click', '.btn-confirm-delete-item', function(e){
        $(this).closest('form').submit();
    });

    // EDIT / UPDATE ITEM BUTTON
    $(document).on('click', '.btn-update-item', function(){
        let id = $(this).data('id');
        let name = $(this).data('name');
        let cat = $(this).data('cat');

        $('#txtItemID').val(id);
        $('#txtNewItemName').val(name);
        $('#txtItemCategory').val(cat);
        $('#addItemModal').modal('show');
    });

    // EDIT CATEGORY BUTTON
    $(document).on('click', '.btn-edit-category', function(){
        let id = $(this).data('id');
        let name = $(this).data('name');
        $('#txtCategoryID').val(id);
        $('#txtCategoryName').val(name);
    });

    // "+" in Item modal opens Category modal and clears input
    $('#btnAddCategoryFromItemModal').on('click', function(){
        $('#txtCategoryID').val('');
        $('#txtCategoryName').val('');
    });

    // Delete category confirmation
    $(document).on('click', '.btn-confirm-delete', function(e){
        let catId = $(this).data('id');
        $('<form method="post" style="display:none;">' +
            '<input type="hidden" name="btnDeleteCategory" value="'+catId+'">' +
          '</form>').appendTo('body').submit();
    });

    // Item Form Submit
    $('#itemForm').on('submit', function(e){
        // No confirmation, just submit
    });

    // Category Form Submit
    $('#categoryForm').on('submit', function(e){
        // No confirmation, just submit
    });
});
</script>

<style>
.modal-content { border-radius: 12px; }
.modal-header { border-radius: 12px 12px 0 0; }
.btn-group .btn { border-radius: 4px; margin-left: 2px; }
#categoryListModal .modal-body {
    max-height: 400px;
    overflow-y: auto;
}
</style>
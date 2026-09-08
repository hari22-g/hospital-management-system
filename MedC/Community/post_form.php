<form method="POST" enctype="multipart/form-data">
    <div class="modal-body">
        <div class="form-group">
            <label for="content" class="text-dark mb-2">Post Content</label>
            <textarea name="content" id="content" class="form-control" rows="3" required></textarea>
        </div>

        <div class="form-group">
            <label for="media_url" class="text-dark mt-2 mb-2">Upload Media</label>
            <input type="file" name="media_url" class="form-control" id="media_url" />
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary" id="addPostBtn">Post</button>
        </div>
    </div>
</form>


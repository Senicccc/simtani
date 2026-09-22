<label>Nama pekerjaan <input name="nama_pekerjaan" value="{{ old('nama_pekerjaan', $jenisPekerjaan->nama_pekerjaan ?? '') }}" required></label>
<label>Deskripsi <textarea name="deskripsi">{{ old('deskripsi', $jenisPekerjaan->deskripsi ?? '') }}</textarea></label>
<label>Satuan <input name="satuan" value="{{ old('satuan', $jenisPekerjaan->satuan ?? '') }}" required></label>
<label>Tarif default <input type="number" name="tarif_default" value="{{ old('tarif_default', $jenisPekerjaan->tarif_default ?? '') }}" required></label>
<label><input type="checkbox" name="status_aktif" value="1" @checked(old('status_aktif', $jenisPekerjaan->status_aktif ?? true))> Aktif</label>

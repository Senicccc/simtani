<label>Nama pekerjaan <input name="nama_pekerjaan" value="{{ old('nama_pekerjaan', $jenisPekerjaan->nama_pekerjaan ?? '') }}" required></label>
<label>Deskripsi <textarea name="deskripsi">{{ old('deskripsi', $jenisPekerjaan->deskripsi ?? '') }}</textarea></label>
<label>Satuan <input name="satuan" value="{{ old('satuan', $jenisPekerjaan->satuan ?? '') }}" required></label>

import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { A as t, C as n, D as r, O as i, d as a, f as o, j as s } from "./catalog-DCoO3A8a.js";
import { h as c } from "./structure-DbqcM-iO.js";
import { AtriumErrorCard as ee, AtriumExtension as l, AtriumIntegrationScreen as u, AtriumNoAccess as te, AtriumPageHeader as ne, AtriumSelect as re, AtriumState as ie, atriumStatus as d, integrationData as f, useAtriumIntegration as p, useAtriumScreen as m } from "@refactor-circus/atrium";
import { UAlert as ae, UBadge as h, UButton as oe, UCard as se, UCheckbox as ce, UFormField as le, UInput as ue, USelect as de, USkeleton as fe, UTable as g } from "@refactor-circus/atrium/ui";
import { Fragment as _, computed as v, createBlock as y, createCommentVNode as b, createElementBlock as x, createElementVNode as S, createTextVNode as C, createVNode as w, defineComponent as T, isRef as pe, openBlock as E, reactive as D, ref as O, renderList as k, shallowRef as A, toDisplayString as j, unref as M, useTemplateRef as me, watch as he, withCtx as N, withModifiers as P } from "vue";
import { useI18n as F } from "vue-i18n";
var I = "showroom:", L = [
	"upsert",
	"create",
	"update"
], R = ["jsonl", "csv"];
function z(e) {
	let t = e?.flow_prefix;
	return typeof t == "string" ? t : I;
}
function ge(e, t) {
	return e.filter((e) => e.slug.startsWith(t)).map((e) => e.slug);
}
function B(e) {
	let t = e.created_at ? new Date(e.created_at).getTime() : NaN;
	return Number.isNaN(t) ? 0 : t;
}
function _e(e, t = 20) {
	return e.flat().sort((e, t) => B(t) - B(e)).slice(0, t);
}
function V(e) {
	let t = e.error?.message;
	return typeof t == "string" && t !== "" ? t : null;
}
function H(e) {
	switch (e) {
		case "completed": return "success";
		case "failed": return "error";
		case "running":
		case "pending":
		case "waiting": return "info";
		case "rolling_back": return "warning";
		default: return "neutral";
	}
}
function ve(e) {
	return {
		format: e.format,
		...e.family ? { family: e.family } : {},
		...e.category ? { category: e.category } : {},
		...e.scope ? { scope: e.scope } : {},
		published: e.published
	};
}
// src/components/transfers/TransferRunsTable.vue?vue&type=script&setup=true&lang.ts
var U = ["data-testid"], W = {
	key: 0,
	class: "mt-1 text-xs text-error"
}, G = { class: "text-muted" }, K = { class: "text-muted" }, q = { class: "flex justify-end gap-2" }, ye = /* @__PURE__ */ T({
	__name: "TransferRunsTable",
	props: {
		runs: {},
		rowActions: {}
	},
	setup(e) {
		let t = e, { t: n } = F(), r = v(() => [
			{
				accessorKey: "flow",
				header: n("showroom.transfers.flow")
			},
			{
				accessorKey: "status",
				header: n("showroom.transfers.status")
			},
			{
				accessorKey: "started_at",
				header: n("showroom.transfers.started")
			},
			{
				accessorKey: "finished_at",
				header: n("showroom.transfers.finished")
			},
			...t.rowActions.length > 0 ? [{
				id: "actions",
				header: "",
				meta: { class: { td: "text-right" } }
			}] : []
		]);
		return (e, i) => {
			let o = h, s = l, c = g;
			return E(), y(c, {
				data: t.runs,
				columns: M(r),
				"data-testid": "showroom-transfers-table"
			}, {
				"flow-cell": N(({ row: { original: e } }) => [S("span", {
					class: "font-mono text-xs",
					"data-testid": `showroom-transfer-run-${e.id}`
				}, j(e.flow), 9, U)]),
				"status-cell": N(({ row: { original: e } }) => [w(o, {
					color: M(H)(e.status),
					variant: "subtle"
				}, {
					default: N(() => [C(j(e.status), 1)]),
					_: 2
				}, 1032, ["color"]), M(V)(e) ? (E(), x("p", W, j(M(V)(e)), 1)) : b("", !0)]),
				"started_at-cell": N(({ row: { original: e } }) => [S("span", G, j(M(a)(e.started_at ?? e.created_at) ?? M(n)("showroom.catalog.none")), 1)]),
				"finished_at-cell": N(({ row: { original: e } }) => [S("span", K, j(M(a)(e.finished_at) ?? M(n)("showroom.catalog.none")), 1)]),
				"actions-cell": N(({ row: { original: e } }) => [S("div", q, [(E(!0), x(_, null, k(t.rowActions, (t) => (E(), y(s, {
					key: t.key,
					extension: t,
					record: e.id
				}, null, 8, ["extension", "record"]))), 128))])]),
				_: 1
			}, 8, ["data", "columns"]);
		};
	}
}), be = {
	class: "space-y-4",
	"data-testid": "showroom-transfers"
}, xe = { class: "space-y-4" }, Se = { class: "grid gap-4 lg:grid-cols-2" }, Ce = { class: "font-semibold" }, we = { class: "font-semibold" }, Te = { class: "font-semibold" }, J = /* @__PURE__ */ T({
	__name: "index",
	setup(a) {
		let { t: l, locale: h } = F(), g = s(), _ = p("impex"), T = i(), k = i(), { data: I, error: B } = m("showroom.transfers"), V = v(() => I.value ? z(I.value.data.options) : B.value ? z(null) : null), H = v(() => B.value ? [] : I.value?.data.sections["row.actions"] ?? []), U = A([]), W = O(!1), G = O(null), K = O(!1);
		async function q() {
			let e = _.client, t = V.value;
			if (e && t !== null) try {
				let n = f(await e.GET("/flows")).data, r = await Promise.all(ge(n, t).map(async (t) => f(await e.GET("/runs", { params: { query: {
					flow: t,
					per_page: 20
				} } })).data));
				U.value = _e(r), K.value = !1, G.value = null;
			} catch (e) {
				K.value = !0, G.value = d(e);
			} finally {
				W.value = !0;
			}
		}
		he(V, () => void q(), { immediate: !0 });
		let J = t(async (e) => n(await e.GET("/families", { params: { query: { per_page: 100 } } }))), Ee = t(c), De = t(async (e) => n(await e.GET("/channels", { params: { query: { per_page: 100 } } }))), Y = O("upsert"), X = A(null), Z = me("fileInput"), Oe = v(() => L.map((e) => ({
			label: l(`showroom.transfers.import.modes.${e}`),
			value: e
		}))), ke = v(() => R.map((e) => ({
			label: l(`showroom.transfers.export.formats.${e}`),
			value: e
		}))), Ae = v(() => J.value.map((e) => ({
			label: o(e.labels, h.value, e.code),
			value: e.code
		}))), je = v(() => Ee.value.map((e) => ({
			label: o(e.labels, h.value, e.code),
			value: e.code
		}))), Me = v(() => De.value.map((e) => ({
			label: o(e.labels, h.value, e.code),
			value: e.code
		}))), Q = D({
			format: "jsonl",
			family: "",
			category: "",
			scope: "",
			published: !1
		}), $ = A(null);
		function Ne(e) {
			X.value = e.target.files?.[0] ?? null;
		}
		async function Pe() {
			let e = X.value;
			if (!g || !e) return;
			let t = new FormData();
			t.append("file", e), t.append("mode", Y.value);
			let r = { run: null };
			await T.run(async () => {
				r.run = n(await g.POST("/imports", {
					body: t,
					bodySerializer: (e) => e
				})).data;
			}) && ($.value = r.run, X.value = null, Z.value?.inputRef && (Z.value.inputRef.value = ""), await q());
		}
		async function Fe() {
			if (!g) return;
			let e = { run: null };
			await k.run(async () => {
				e.run = n(await g.POST("/exports", { body: r(ve(Q)) })).data;
			}) && ($.value = e.run, await q());
		}
		return (t, n) => {
			let r = ne, i = ae, a = ue, o = le, s = de, c = oe, d = se, f = re, p = ce, m = te, h = ee, g = fe, v = ie, D = u;
			return E(), x("div", be, [w(r, { title: M(l)("showroom.transfers.title") }, null, 8, ["title"]), w(D, { "screen-key": "showroom.transfers" }, {
				default: N(() => [S("div", xe, [
					M($) ? (E(), y(i, {
						key: 0,
						color: "success",
						variant: "subtle",
						icon: "i-lucide-circle-check",
						title: M(l)("showroom.transfers.started_notice", { flow: M($).flow }),
						"data-testid": "showroom-transfer-started"
					}, null, 8, ["title"])) : b("", !0),
					S("div", Se, [w(d, { "data-testid": "showroom-transfer-import" }, {
						header: N(() => [S("h2", Ce, j(M(l)("showroom.transfers.import.title")), 1)]),
						default: N(() => [S("form", {
							class: "flex flex-col gap-3",
							"data-testid": "showroom-transfer-import-form",
							onSubmit: P(Pe, ["prevent"])
						}, [
							w(e, { action: M(T) }, null, 8, ["action"]),
							w(o, {
								label: M(l)("showroom.transfers.import.file"),
								hint: M(l)("showroom.transfers.import.file_hint"),
								error: M(T).error("file"),
								required: ""
							}, {
								default: N(() => [w(a, {
									ref_key: "fileInput",
									ref: Z,
									type: "file",
									accept: ".csv,.jsonl,text/csv",
									class: "w-full",
									"data-testid": "showroom-transfer-import-file",
									onChange: Ne
								}, null, 512)]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							w(o, {
								label: M(l)("showroom.transfers.import.mode"),
								error: M(T).error("mode")
							}, {
								default: N(() => [w(s, {
									modelValue: M(Y),
									"onUpdate:modelValue": n[0] ||= (e) => pe(Y) ? Y.value = e : null,
									items: M(Oe),
									class: "w-full",
									"data-testid": "showroom-transfer-import-mode"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]),
							S("div", null, [w(c, {
								type: "submit",
								icon: "i-lucide-upload",
								loading: M(T).busy.value,
								"data-testid": "showroom-transfer-import-submit"
							}, {
								default: N(() => [C(j(M(l)("showroom.transfers.import.submit")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32)]),
						_: 1
					}), w(d, { "data-testid": "showroom-transfer-export" }, {
						header: N(() => [S("h2", we, j(M(l)("showroom.transfers.export.title")), 1)]),
						default: N(() => [S("form", {
							class: "flex flex-col gap-3",
							"data-testid": "showroom-transfer-export-form",
							onSubmit: P(Fe, ["prevent"])
						}, [
							w(e, { action: M(k) }, null, 8, ["action"]),
							w(o, {
								label: M(l)("showroom.transfers.export.format"),
								error: M(k).error("format")
							}, {
								default: N(() => [w(s, {
									modelValue: M(Q).format,
									"onUpdate:modelValue": n[1] ||= (e) => M(Q).format = e,
									items: M(ke),
									class: "w-full",
									"data-testid": "showroom-transfer-export-format"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]),
							w(o, {
								label: M(l)("showroom.catalog.fields.family"),
								error: M(k).error("family")
							}, {
								default: N(() => [w(f, {
									modelValue: M(Q).family,
									"onUpdate:modelValue": n[2] ||= (e) => M(Q).family = e,
									items: M(Ae),
									blank: M(l)("showroom.transfers.export.all_families"),
									"blank-value": null,
									class: "w-full",
									"data-testid": "showroom-transfer-export-family"
								}, null, 8, [
									"modelValue",
									"items",
									"blank"
								])]),
								_: 1
							}, 8, ["label", "error"]),
							w(o, {
								label: M(l)("showroom.transfers.export.category_tree"),
								error: M(k).error("category")
							}, {
								default: N(() => [w(f, {
									modelValue: M(Q).category,
									"onUpdate:modelValue": n[3] ||= (e) => M(Q).category = e,
									items: M(je),
									blank: M(l)("showroom.transfers.export.all_categories"),
									"blank-value": null,
									class: "w-full",
									"data-testid": "showroom-transfer-export-category"
								}, null, 8, [
									"modelValue",
									"items",
									"blank"
								])]),
								_: 1
							}, 8, ["label", "error"]),
							w(o, {
								label: M(l)("showroom.catalog.fields.channel"),
								error: M(k).error("scope")
							}, {
								default: N(() => [w(f, {
									modelValue: M(Q).scope,
									"onUpdate:modelValue": n[4] ||= (e) => M(Q).scope = e,
									items: M(Me),
									blank: M(l)("showroom.transfers.export.no_channel"),
									"blank-value": null,
									class: "w-full",
									"data-testid": "showroom-transfer-export-scope"
								}, null, 8, [
									"modelValue",
									"items",
									"blank"
								])]),
								_: 1
							}, 8, ["label", "error"]),
							w(p, {
								modelValue: M(Q).published,
								"onUpdate:modelValue": n[5] ||= (e) => M(Q).published = e,
								label: M(l)("showroom.transfers.export.published"),
								"data-testid": "showroom-transfer-export-published"
							}, null, 8, ["modelValue", "label"]),
							S("div", null, [w(c, {
								type: "submit",
								icon: "i-lucide-download",
								loading: M(k).busy.value,
								"data-testid": "showroom-transfer-export-submit"
							}, {
								default: N(() => [C(j(M(l)("showroom.transfers.export.submit")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32)]),
						_: 1
					})]),
					w(d, { "data-testid": "showroom-transfers-runs" }, {
						header: N(() => [S("h2", Te, j(M(l)("showroom.transfers.recent")), 1)]),
						default: N(() => [M(_).active ? M(K) && M(G) === 403 ? (E(), y(m, {
							key: 1,
							compact: ""
						})) : M(K) ? (E(), y(h, {
							key: 2,
							message: M(l)("showroom.transfers.runs_failed")
						}, null, 8, ["message"])) : M(W) ? M(U).length === 0 ? (E(), y(v, {
							key: 4,
							compact: "",
							title: M(l)("showroom.transfers.no_runs"),
							"data-testid": "showroom-transfers-no-runs"
						}, null, 8, ["title"])) : (E(), y(ye, {
							key: 5,
							runs: M(U),
							"row-actions": M(H)
						}, null, 8, ["runs", "row-actions"])) : (E(), y(g, {
							key: 3,
							class: "h-24"
						})) : (E(), y(i, {
							key: 0,
							color: "neutral",
							variant: "subtle",
							icon: "i-lucide-plug",
							title: M(l)("showroom.transfers.requires_impex"),
							description: M(l)("showroom.transfers.requires_impex_hint"),
							"data-testid": "showroom-transfers-requires-impex"
						}, null, 8, ["title", "description"]))]),
						_: 1
					})
				])]),
				_: 1
			})]);
		};
	}
});
export { J as default };
